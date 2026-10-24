<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surat:make-admin {email : Alamat email pengguna} {--role=admin : Peran admin atau moderator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menetapkan hak akses Admin atau Moderator untuk pengguna terdaftar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->option('role');

        if (!in_array($role, ['admin', 'moderator'])) {
            $this->error("Peran tidak valid. Gunakan 'admin' atau 'moderator'.");
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Pengguna dengan email '{$email}' tidak ditemukan.");
            return 1;
        }

        AdminUser::updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => $role,
                'disabled_at' => null,
                'created_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
            ]
        );

        $this->info("Berhasil! Pengguna '{$user->name}' ({$email}) kini memiliki peran '{$role}'.");
        return 0;
    }
}
