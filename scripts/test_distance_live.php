<?php
declare(strict_types=1);

// Test EN CONDITIONS RÉELLES des services de l'IGN (nécessite Internet). À lancer à la main.
require __DIR__ . '/../vendor/autoload.php';

use App\Service\DistanceService;

$service = new DistanceService();
foreach ([
    ['Place de la Bourse', '33000', 'Bordeaux'],
    ['Avenue Marcel Dassault', '33700', 'Mérignac'],
    ['Place Abel Surchamp', '33500', 'Libourne'],
] as [$adresse, $cp, $ville]) {
    try {
        $r = $service->compute($adresse, $cp, $ville);
        printf("%-26s %s %-10s -> Bordeaux : %-3s  distance : %5.1f km  estimée : %s\n",
            $adresse, $cp, $ville, $r['bordeaux'] ? 'oui' : 'non', $r['km10'] / 10, $r['estime'] ? 'OUI (itinéraire indisponible)' : 'non');
    } catch (Throwable $e) {
        echo "$adresse : ÉCHEC : " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "Réponse brute du service d'itinéraire (Bordeaux centre -> aéroport de Mérignac) :" . PHP_EOL;
$url = 'https://data.geopf.fr/navigation/itineraire?' . http_build_query([
    'resource' => 'bdtopo-osrm', 'start' => '-0.5792,44.8378', 'end' => '-0.7156,44.8283',
    'profile' => 'car', 'optimization' => 'fastest', 'getSteps' => 'false', 'distanceUnit' => 'kilometer',
]);
$reponse = json_decode((string) @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 8]])), true);
if (is_array($reponse)) {
    echo 'Clés : ' . implode(', ', array_keys($reponse)) . PHP_EOL;
    echo 'distance = ' . json_encode($reponse['distance'] ?? null) . ' ; distanceUnit = ' . json_encode($reponse['distanceUnit'] ?? null) . PHP_EOL;
} else {
    echo "Pas de réponse exploitable." . PHP_EOL;
}
