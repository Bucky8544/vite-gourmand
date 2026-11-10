<?php
declare(strict_types=1);

namespace App\Service;

final class AdminStatsBuilder
{
    /**
     * Fusionne les menus de la base (tous, y compris ceux sans commande) et les statistiques de MongoDB.
     * Un menu supprimé depuis reste visible avec le titre conservé dans MongoDB.
     *
     * @param list<array{id:int|string,titre:string}> $menus
     * @param list<array{menu_id:int,menu_titre:string,nombre_commandes:int,chiffre_affaires_centimes:int}> $stats
     * @return list<array{menu_id:int,titre:string,commandes:int,ca_centimes:int,supprime:bool}>
     */
    public static function merge(array $menus, array $stats): array
    {
        $lignes = [];
        foreach ($menus as $m) {
            $id = (int) $m['id'];
            $lignes[$id] = ['menu_id' => $id, 'titre' => (string) $m['titre'], 'commandes' => 0, 'ca_centimes' => 0, 'supprime' => false];
        }
        foreach ($stats as $s) {
            $id = (int) $s['menu_id'];
            if (!isset($lignes[$id])) {
                $lignes[$id] = ['menu_id' => $id, 'titre' => $s['menu_titre'] . ' (menu supprimé)', 'commandes' => 0, 'ca_centimes' => 0, 'supprime' => true];
            }
            $lignes[$id]['commandes'] += (int) $s['nombre_commandes'];
            $lignes[$id]['ca_centimes'] += (int) $s['chiffre_affaires_centimes'];
        }

        $lignes = array_values($lignes);
        usort($lignes, static fn (array $a, array $b): int => ($b['commandes'] <=> $a['commandes'])
            ?: ($b['ca_centimes'] <=> $a['ca_centimes'])
            ?: strcmp($a['titre'], $b['titre']));

        return $lignes;
    }
}
