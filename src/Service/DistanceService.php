<?php
declare(strict_types=1);

namespace App\Service;

use Closure;
use Throwable;

/**
 * Distance de livraison depuis le point de départ de l'entreprise.
 * Services publics de l'IGN (Géoplateforme) : géocodage de l'adresse, puis calcul d'itinéraire routier.
 */
final class DistanceService
{
    public const BORDEAUX_INSEE = '33063';

    private const GEOCODE_URL = 'https://data.geopf.fr/geocodage/search';
    private const ROUTE_URL = 'https://data.geopf.fr/navigation/itineraire';
    private const SCORE_MINIMUM = 0.5;
    private const DETOUR = 1.3;        // majoration de la distance à vol d'oiseau si l'itinéraire est indisponible
    private const KM_MAXIMUM = 2000.0; // au-delà, la réponse est jugée aberrante

    private Closure $http;
    private float $departLon;
    private float $departLat;

    /** $http : fonction (string $url): string, remplaçable dans les tests. */
    public function __construct(?Closure $http = null, ?float $departLon = null, ?float $departLat = null)
    {
        $this->http = $http ?? self::curlGet(...);
        $this->departLon = $departLon ?? (float) (getenv('SHOP_LON') ?: -0.5792);
        $this->departLat = $departLat ?? (float) (getenv('SHOP_LAT') ?: 44.8378);
    }

    /**
     * @return array{km10:int,bordeaux:bool,estime:bool} km10 : distance en dixièmes de km (0 à Bordeaux)
     * @throws DistanceException adresse introuvable ou service indisponible
     */
    public function compute(string $adresse, string $codePostal, string $ville): array
    {
        $lieu = $this->geocode($adresse, $codePostal, $ville);

        if ($lieu['citycode'] === self::BORDEAUX_INSEE) {
            return ['km10' => 0, 'bordeaux' => true, 'estime' => false];
        }

        try {
            $km = $this->routeKm($lieu['lon'], $lieu['lat']);
            $estime = false;
        } catch (Throwable $e) {
            error_log('Itinéraire indisponible, distance estimée : ' . $e->getMessage());
            $km = self::haversineKm($this->departLat, $this->departLon, $lieu['lat'], $lieu['lon']) * self::DETOUR;
            $estime = true;
        }

        return ['km10' => (int) round($km * 10), 'bordeaux' => false, 'estime' => $estime];
    }

    /** Distance à vol d'oiseau en kilomètres. */
    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * 6371.0 * asin(min(1.0, sqrt($a)));
    }

    /** @return array{lon:float,lat:float,citycode:string} */
    private function geocode(string $adresse, string $codePostal, string $ville): array
    {
        $url = self::GEOCODE_URL . '?' . http_build_query([
            'q' => $adresse . ' ' . $codePostal . ' ' . $ville,
            'postcode' => $codePostal,
            'limit' => 1,
        ]);

        try {
            $data = json_decode(($this->http)($url), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            error_log('Géocodage indisponible : ' . $e->getMessage());
            throw new DistanceException('Le calcul de la distance est momentanément indisponible. '
                . 'Réessayez dans quelques instants ou contactez-nous.');
        }

        $feature = is_array($data) ? ($data['features'][0] ?? null) : null;
        $coord = is_array($feature) ? ($feature['geometry']['coordinates'] ?? null) : null;
        $props = is_array($feature) ? ($feature['properties'] ?? []) : [];

        if (!is_array($coord) || count($coord) < 2 || !isset($props['citycode']) || (float) ($props['score'] ?? 0) < self::SCORE_MINIMUM) {
            throw new DistanceException("Nous n'avons pas trouvé cette adresse. Vérifiez la rue, le code postal et la ville.");
        }

        return ['lon' => (float) $coord[0], 'lat' => (float) $coord[1], 'citycode' => (string) $props['citycode']];
    }

    private function routeKm(float $lon, float $lat): float
    {
        $url = self::ROUTE_URL . '?' . http_build_query([
            'resource' => 'bdtopo-osrm',
            'start' => $this->departLon . ',' . $this->departLat,
            'end' => $lon . ',' . $lat,
            'profile' => 'car',
            'optimization' => 'fastest',
            'getSteps' => 'false',
            'distanceUnit' => 'kilometer',
        ]);

        $data = json_decode(($this->http)($url), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['distance']) || !is_numeric($data['distance'])) {
            throw new \UnexpectedValueException("Réponse d'itinéraire sans distance");
        }

        $distance = (float) $data['distance'];
        $km = strtolower((string) ($data['distanceUnit'] ?? 'kilometer')) === 'meter' ? $distance / 1000 : $distance;
        if ($km < 0 || $km > self::KM_MAXIMUM) {
            throw new \UnexpectedValueException('Distance aberrante : ' . $km);
        }

        return $km;
    }

    private static function curlGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'ViteGourmand-ECF/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $corps = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $erreur = curl_error($ch);
        curl_close($ch);

        if ($corps === false || $code !== 200) {
            throw new \RuntimeException("HTTP $code $erreur");
        }

        return (string) $corps;
    }
}
