<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Models\User;

/**
 * Génère le menu sidebar filtré selon le rôle de l'utilisateur.
 */
class MenuService
{
    public function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $slug = $user->role?->slug;
        $userSlug = $slug instanceof RoleSlug ? $slug->value : (string) $slug;

        return collect($this->items())
            ->filter(fn (array $item) => in_array($userSlug, $item['roles'], true))
            ->values()
            ->all();
    }

    private function items(): array
    {
        return [
            [
                'label' => 'Tableau de bord',
                'route' => 'dashboard',
                'icon' => 'bi-speedometer2',
                'roles' => ['admin', 'manager', 'cashier', 'driver'],
            ],
            [
                'label' => 'Produits',
                'route' => 'products.index',
                'icon' => 'bi-controller',
                'roles' => ['admin', 'manager'],
            ],
            [
                'label' => 'Catégories',
                'route' => 'categories.index',
                'icon' => 'bi-tags',
                'roles' => ['admin', 'manager'],
            ],
            [
                'label' => 'Fournisseurs',
                'route' => 'suppliers.index',
                'icon' => 'bi-truck',
                'roles' => ['admin', 'manager'],
            ],
            [
                'label' => 'Mouvements de stock',
                'route' => 'stock.index',
                'icon' => 'bi-arrow-down-up',
                'roles' => ['admin', 'manager'],
            ],
            [
                'label' => 'Clients',
                'route' => 'customers.index',
                'icon' => 'bi-people',
                'roles' => ['admin', 'manager', 'cashier'],
            ],
            [
                'label' => 'Ventes',
                'route' => 'sales.index',
                'icon' => 'bi-cart-check',
                'roles' => ['admin', 'manager', 'cashier'],
            ],
            [
                'label' => 'Factures',
                'route' => 'invoices.index',
                'icon' => 'bi-receipt',
                'roles' => ['admin', 'manager', 'cashier'],
            ],
            [
                'label' => 'Gestion des retours',
                'route' => 'returns.index',
                'icon' => 'bi-arrow-return-left',
                'roles' => ['admin', 'manager', 'cashier'],
            ],
            [
                'label' => 'Garanties',
                'route' => 'warranties.index',
                'icon' => 'bi-shield-check',
                'roles' => ['admin', 'manager', 'cashier'],
            ],
            [
                'label' => 'Rapports',
                'route' => 'reports.index',
                'icon' => 'bi-graph-up',
                'roles' => ['admin', 'manager', 'cashier'],
            ],
            [
                'label' => 'Utilisateurs',
                'route' => 'users.index',
                'icon' => 'bi-person-gear',
                'roles' => ['admin', 'manager'],
            ],
        ];
    }
}
