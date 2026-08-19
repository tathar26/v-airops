<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Tenant;
use App\Models\User;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TenantSettings extends Component
{
    use WithFileUploads;

    public $activeTab = 'general';

    // General Settings
    public $name = '';
    public $icao = '';
    public $secondary_icaos = [];
    public $newSecondaryIcao = '';
    public $newHubIcao = '';
    public $accent_color = '';
    public $bg_color = '';
    public $panel_bg_color = '';
    public $panel_text_color = '';
    public $card_bg_color = '';
    public $card_text_color = '';
    public $card_muted_text_color = '';
    public $button_bg_color = '';
    public $button_text_color = '';
    public $button_secondary_bg_color = '';
    public $button_secondary_text_color = '';
    public $input_bg_color = '';
    public $input_text_color = '';
    public $input_border_color = '';
    public $logo;
    public $default_simbrief_ofp_format = 'lido';

    // User Management
    public $showUserModal = false;
    public $editingUserId = null;
    public $userName = '';
    public $userEmail = '';
    public $userPassword = '';
    public $userRole = 'Pilot';

    public $simbriefFormats = [];

    public function mount()
    {
        $tenant = auth()->user()->tenant;
        $this->name = $tenant->name;
        $this->icao = $tenant->icao;
        $this->secondary_icaos = $tenant->secondary_icaos ?? [];
        $this->accent_color = $tenant->accent_color ?? '#f97316';
        $this->bg_color = $tenant->bg_color ?? '#0f1117';
        $this->panel_bg_color = $tenant->panel_bg_color ?? $this->accent_color;
        $this->card_bg_color = $tenant->card_bg_color ?? $this->bg_color;

        $hexLuminance = function(?string $hex): float {
            if (!$hex) return 0.0;
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            if (strlen($hex) !== 6) return 0.0;
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;
            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        $this->panel_text_color = $tenant->panel_text_color ?? ($hexLuminance($this->panel_bg_color) > 0.5 ? '#0f172a' : '#f8fafc');
        $this->card_text_color = $tenant->card_text_color ?? ($hexLuminance($this->card_bg_color) > 0.5 ? '#0f172a' : '#ffffff');
        $this->card_muted_text_color = $tenant->card_muted_text_color ?? ($hexLuminance($this->card_bg_color) > 0.5 ? '#475569' : '#94a3b8');

        $this->button_bg_color = $tenant->button_bg_color ?? $this->accent_color;
        $this->button_text_color = $tenant->button_text_color ?? '#ffffff';
        $this->button_secondary_bg_color = $tenant->button_secondary_bg_color ?? '#1f2937';
        $this->button_secondary_text_color = $tenant->button_secondary_text_color ?? '#f3f4f6';
        $this->input_bg_color = $tenant->input_bg_color ?? '#0a0d14';
        $this->input_text_color = $tenant->input_text_color ?? '#ffffff';
        $this->input_border_color = $tenant->input_border_color ?? '#374151';
        $this->default_simbrief_ofp_format = $tenant->default_simbrief_ofp_format ?? 'lido';

        // Fetch simbrief formats and cache for 24 hours
        $this->simbriefFormats = \Illuminate\Support\Facades\Cache::remember('simbrief_formats', 86400, function () {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get('http://www.simbrief.com/api/inputs.list.json');
                if ($response->successful()) {
                    $layouts = $response->json('layouts');
                    $formats = [];
                    foreach ($layouts as $key => $layout) {
                        $formats[$key] = $layout['name_long'];
                    }
                    asort($formats);
                    return $formats;
                }
            } catch (\Exception $e) {
                // Fallback
            }

            return [
                'lido' => 'LIDO - SimBrief Default',
                'ryr' => 'RYR - Ryanair',
                'aal' => 'AAL - American Airlines',
                'baw' => 'BAW - British Airways',
                'dal' => 'DAL - Delta Air Lines',
                'dlh' => 'DLH - Lufthansa',
                'ezy' => 'EZY - easyJet',
                'swa' => 'SWA - Southwest Airlines',
                'ual' => 'UAL - United Airlines',
            ];
        });
    }

    public function addSecondaryIcao()
    {
        $this->validate([
            'newSecondaryIcao' => 'required|string|min:2|max:4|alpha',
        ]);

        $code = strtoupper(trim($this->newSecondaryIcao));
        $primary = strtoupper(trim($this->icao));

        if ($code === $primary) {
            $this->addError('newSecondaryIcao', "'{$code}' is already set as the primary airline ICAO.");
            return;
        }

        if (in_array($code, $this->secondary_icaos)) {
            $this->addError('newSecondaryIcao', "'{$code}' is already added as a secondary ICAO.");
            return;
        }

        $this->secondary_icaos[] = $code;
        $this->newSecondaryIcao = '';
        $this->resetErrorBag('newSecondaryIcao');
    }

    public function removeSecondaryIcao($index)
    {
        if (isset($this->secondary_icaos[$index])) {
            unset($this->secondary_icaos[$index]);
            $this->secondary_icaos = array_values($this->secondary_icaos);
        }
    }

    public function saveSettings()
    {
        $tenant = auth()->user()->tenant;
        
        $this->validate([
            'name' => 'required|string|max:255',
            'icao' => 'required|string|min:2|max:4|alpha',
            'accent_color' => 'required|string|max:7',
            'bg_color' => 'required|string|max:7',
            'panel_bg_color' => 'nullable|string|max:7',
            'panel_text_color' => 'nullable|string|max:7',
            'card_bg_color' => 'nullable|string|max:7',
            'card_text_color' => 'nullable|string|max:7',
            'card_muted_text_color' => 'nullable|string|max:7',
            'button_bg_color' => 'nullable|string|max:7',
            'button_text_color' => 'nullable|string|max:7',
            'button_secondary_bg_color' => 'nullable|string|max:7',
            'button_secondary_text_color' => 'nullable|string|max:7',
            'input_bg_color' => 'nullable|string|max:7',
            'input_text_color' => 'nullable|string|max:7',
            'input_border_color' => 'nullable|string|max:7',
            'logo' => 'nullable|image|max:1024',
            'default_simbrief_ofp_format' => 'required|string|max:20',
        ]);

        $icaoUpper = strtoupper(trim($this->icao));

        // Duplicate primary ICAO check
        $existing = Tenant::whereRaw('UPPER(icao) = ?', [$icaoUpper])
            ->where('id', '!=', $tenant->id)
            ->first();

        if ($existing) {
            $this->addError('icao', "The ICAO code '{$this->icao}' is already registered by '{$existing->name}'. Duplicate airlines are not permitted.");
            return;
        }

        $tenant->name = $this->name;
        $tenant->icao = $icaoUpper;
        $tenant->secondary_icaos = array_values(array_unique(array_filter($this->secondary_icaos)));
        $tenant->accent_color = $this->accent_color;
        $tenant->bg_color = $this->bg_color;
        $tenant->panel_bg_color = $this->panel_bg_color ?: $this->accent_color;
        $tenant->panel_text_color = $this->panel_text_color ?: null;
        $tenant->card_bg_color = $this->card_bg_color ?: $this->bg_color;
        $tenant->card_text_color = $this->card_text_color ?: null;
        $tenant->card_muted_text_color = $this->card_muted_text_color ?: null;
        $tenant->button_bg_color = $this->button_bg_color ?: $this->accent_color;
        $tenant->button_text_color = $this->button_text_color ?: '#ffffff';
        $tenant->button_secondary_bg_color = $this->button_secondary_bg_color ?: '#1f2937';
        $tenant->button_secondary_text_color = $this->button_secondary_text_color ?: '#f3f4f6';
        $tenant->input_bg_color = $this->input_bg_color ?: '#0a0d14';
        $tenant->input_text_color = $this->input_text_color ?: '#ffffff';
        $tenant->input_border_color = $this->input_border_color ?: '#374151';
        $tenant->default_simbrief_ofp_format = $this->default_simbrief_ofp_format;

        if ($this->logo) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->logo_path = $this->logo->store('logos', 'public');
        }

        $tenant->save();

        session()->flash('settings_message', 'Settings saved successfully.');
    }

    public function addHub()
    {
        $this->validate(['newHubIcao' => 'required|string|size:4']);
        $icao = strtoupper($this->newHubIcao);
        
        $airport = \App\Models\Airport::fetchAndCreate($icao);

        if ($airport) {
            \App\Models\TenantHub::firstOrCreate([
                'tenant_id' => auth()->user()->tenant_id,
                'airport_id' => $airport->id,
                'is_base' => true
            ]);
            $this->newHubIcao = '';
            session()->flash('hub_message', 'Base added successfully.');
        } else {
            $this->addError('newHubIcao', 'Airport not found or could not be fetched.');
        }
    }

    public function removeHub($hubId)
    {
        \App\Models\TenantHub::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $hubId)
            ->delete();
        session()->flash('hub_message', 'Base removed successfully.');
    }

    public function openUserModal()
    {
        $this->reset(['editingUserId', 'userName', 'userEmail', 'userPassword', 'userRole']);
        $this->showUserModal = true;
    }

    public function editUser($id)
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editingUserId = $user->id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->userPassword = ''; // leave empty
        $this->userRole = $user->roles->first()->name ?? 'Pilot';
        $this->showUserModal = true;
    }

    public function saveUser()
    {
        $rules = [
            'userName' => 'required|string|max:255',
            'userEmail' => 'required|email|max:255|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'userRole' => 'required|string|in:Pilot,VA Owner',
        ];

        if (!$this->editingUserId || $this->userPassword) {
            $rules['userPassword'] = 'required|string|min:8';
        }

        $this->validate($rules);

        if ($this->editingUserId) {
            $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingUserId);
            $user->name = $this->userName;
            $user->email = $this->userEmail;
            if ($this->userPassword) {
                $user->password = Hash::make($this->userPassword);
            }
            $user->save();
            $user->syncRoles([$this->userRole]);
        } else {
            $user = User::create([
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $this->userName,
                'email' => $this->userEmail,
                'password' => Hash::make($this->userPassword),
            ]);
            $user->assignRole($this->userRole);
        }

        $this->showUserModal = false;
        session()->flash('user_message', 'User saved successfully.');
    }

    public function deleteUser($id)
    {
        if (auth()->id() == $id) {
            session()->flash('user_message', 'You cannot delete yourself.');
            return;
        }

        User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        session()->flash('user_message', 'User deleted successfully.');
    }

    public function render()
    {
        $users = User::with('roles')->where('tenant_id', auth()->user()->tenant_id)->get();
        $hubs = \App\Models\TenantHub::with('airport')->where('tenant_id', auth()->user()->tenant_id)->where('is_base', true)->get();
        
        return view('livewire.tenant-settings', [
            'users' => $users,
            'hubs' => $hubs
        ])->layout('layouts.app');
    }
}
