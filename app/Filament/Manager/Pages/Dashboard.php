<?php

namespace App\Filament\Manager\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Manager — Approvals & Analytics';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
}
