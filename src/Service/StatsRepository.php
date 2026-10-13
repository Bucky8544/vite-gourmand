<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Statistiques de commandes (MongoDB) pour l'espace administrateur.
 * Un document par commande acceptée. Montants en centimes (entiers).
 */
final class StatsRepository
{
    private const TYPE_MAP = ['root' => 'array', 'document' => 'array'];

    public function __construct(private Collection $collection)
    {
    }

    public static function fromEnv(): self
    {
        $client = new Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017');
        $base = getenv('MONGO_DB') ?: 'vite_gourmand';

        return new self($client->selectCollection($base, 'commandes_stats'));
    }

    public function creerIndex(): void
    {
        $this->collection->createIndex(['commande_id' => 1], ['unique' => true]);
        $this->collection->createIndex(['menu_id' => 1, 'date_prestation' => 1]);
    }

    /** Crée ou met à jour le document d'une commande (sans doublon possible). */
    public function enregistrer(
        int $commandeId,
        int $menuId,
        string $menuTitre,
        DateTimeInterface $datePrestation,
        int $nombrePersonne,
        int $montantCentimes
    ): void {
        $this->collection->updateOne(
            ['commande_id' => $commandeId],
            [
                '$set' => [
                    'menu_id' => $menuId,
                    'menu_titre' => $menuTitre,
                    'date_prestation' => new UTCDateTime($datePrestation),
                    'nombre_personne' => $nombrePersonne,
                    'montant_centimes' => $montantCentimes,
                ],
                '$setOnInsert' => ['date_acceptation' => new UTCDateTime()],
            ],
            ['upsert' => true]
        );
    }

    /** À appeler si une commande acceptée est ensuite annulée. */
    public function supprimer(int $commandeId): void
    {
        $this->collection->deleteOne(['commande_id' => $commandeId]);
    }

    /**
     * Nombre de commandes et chiffre d'affaires par menu (pour le graphique).
     *
     * @return list<array{menu_id:int, menu_titre:string, nombre_commandes:int, chiffre_affaires_centimes:int}>
     */
    public function commandesParMenu(?DateTimeInterface $debut = null, ?DateTimeInterface $fin = null): array
    {
        $pipeline = [];
        $filtre = $this->filtre(null, $debut, $fin);
        if ($filtre !== []) {
            $pipeline[] = ['$match' => $filtre];
        }
        $pipeline[] = ['$sort' => ['date_acceptation' => 1]];
        $pipeline[] = ['$group' => [
            '_id' => '$menu_id',
            'menu_titre' => ['$last' => '$menu_titre'],
            'nombre_commandes' => ['$sum' => 1],
            'chiffre_affaires_centimes' => ['$sum' => '$montant_centimes'],
        ]];
        $pipeline[] = ['$sort' => ['nombre_commandes' => -1, '_id' => 1]];

        $lignes = iterator_to_array(
            $this->collection->aggregate($pipeline, ['typeMap' => self::TYPE_MAP]),
            false
        );

        return array_map(static fn (array $l): array => [
            'menu_id' => (int) $l['_id'],
            'menu_titre' => (string) $l['menu_titre'],
            'nombre_commandes' => (int) $l['nombre_commandes'],
            'chiffre_affaires_centimes' => (int) $l['chiffre_affaires_centimes'],
        ], $lignes);
    }

    /**
     * Chiffre d'affaires, filtrable par menu et par période.
     *
     * @return array{nombre_commandes:int, chiffre_affaires_centimes:int}
     */
    public function chiffreAffaires(
        ?int $menuId = null,
        ?DateTimeInterface $debut = null,
        ?DateTimeInterface $fin = null
    ): array {
        $pipeline = [];
        $filtre = $this->filtre($menuId, $debut, $fin);
        if ($filtre !== []) {
            $pipeline[] = ['$match' => $filtre];
        }
        $pipeline[] = ['$group' => [
            '_id' => null,
            'nombre_commandes' => ['$sum' => 1],
            'chiffre_affaires_centimes' => ['$sum' => '$montant_centimes'],
        ]];

        $lignes = iterator_to_array(
            $this->collection->aggregate($pipeline, ['typeMap' => self::TYPE_MAP]),
            false
        );

        return [
            'nombre_commandes' => (int) ($lignes[0]['nombre_commandes'] ?? 0),
            'chiffre_affaires_centimes' => (int) ($lignes[0]['chiffre_affaires_centimes'] ?? 0),
        ];
    }

    private function filtre(?int $menuId, ?DateTimeInterface $debut, ?DateTimeInterface $fin): array
    {
        $filtre = [];
        if ($menuId !== null) {
            $filtre['menu_id'] = $menuId;
        }
        if ($debut !== null || $fin !== null) {
            $periode = [];
            if ($debut !== null) {
                $periode['$gte'] = new UTCDateTime($debut);
            }
            if ($fin !== null) {
                $periode['$lte'] = new UTCDateTime($fin);
            }
            $filtre['date_prestation'] = $periode;
        }

        return $filtre;
    }
}
