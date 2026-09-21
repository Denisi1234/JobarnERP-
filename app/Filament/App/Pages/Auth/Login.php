<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.app.pages.auth.login';

    public function mount(): void
    {
        if (\Filament\Facades\Filament::auth()->check()) {
            redirect()->to($this->getRedirectUrl());
            return;
        }

        $this->form->fill([
            'email' => old('email'),
            'role' => 'reception',
            'remember' => true,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getRoleFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getRoleFormComponent(): Component
    {
        return Select::make('role')
            ->label('Select Role / Portal')
            ->options([
                'reception' => 'Receptionist (Front Desk)',
                'it' => 'IT Support (PC Maintenance)',
                'sales' => 'Sales (Billing & Invoicing)',
                'manager' => 'Manager (Executive)',
                'admin' => 'Administrator',
            ])
            ->default('reception')
            ->nullable()
            ->native(true)
            ->extraInputAttributes(['tabindex' => 2]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        try {
            if (! \Filament\Facades\Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
                $this->throwFailureValidationException();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            \Filament\Notifications\Notification::make()
                ->title('Database error')
                ->body('Database connection issue: ' . $e->getMessage())
                ->danger()
                ->send();
            return null;
        }

        $user = \Filament\Facades\Filament::auth()->user();

        if (
            ($user instanceof \Filament\Models\Contracts\FilamentUser) &&
            (! $user->canAccessPanel(\Filament\Facades\Filament::getCurrentPanel()))
        ) {
            \Filament\Facades\Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        $redirectUrl = $this->getRedirectUrl();

        return new class($redirectUrl) implements LoginResponse {
            public function __construct(protected string $url) {}

            public function toResponse($request): \Illuminate\Http\RedirectResponse | \Livewire\Features\SupportRedirects\Redirector
            {
                return redirect()->to($this->url);
            }
        };
    }

    protected function getRedirectUrl(): string
    {
        $user = auth()->user();
        if (!$user) return '/login';

        $formData = $this->form->getState();
        // Keep the user in the portal assigned to their account.  A stale
        // is_admin value must not override an IT user's assigned role.
        $selectedRole = $user->role ?? 'reception';

        // Admin portal is separate at /admin — is_admin users selecting Administrator go there
        if ($selectedRole === 'admin' && $user->is_admin) {
            return '/admin';
        }

        return match ($selectedRole) {
            'reception' => '/reception',
            'it' => '/it',
            'sales' => '/sales',
            'manager' => '/manager',
            'admin' => $user->is_admin ? '/admin' : '/reception',
            default => '/reception',
        };
    }
}
