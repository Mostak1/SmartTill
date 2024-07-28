<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Http\File as IlluminateFile;

class BackupAndEncrypt extends Command
{
    protected $signature = 'backup:encrypt';
    protected $description = 'Create, encrypt, and upload a backup to Google Drive';

    public function handle()
    {
        // Step 1: Create the backup and store it locally
        $this->call('backup:run');

        // Step 2: Locate the created backup zip file
        $backupPath =public_path('uploads/LacunaERP');
        $backupFiles = glob($backupPath . '/*.zip');

        if (empty($backupFiles)) {
            $this->error('Backup file not found in: ' . $backupPath);
            return;
        }

        $backupFile = $backupFiles[0]; // Assuming only one backup file is created

        // Step 3: Encrypt the backup
        $encryptedBackupPath = $backupPath . '/encrypted_backup3.zip';
        $password = '123456';
        $opensslPath = 'C:\Program Files\OpenSSL-Win64\bin\openssl.exe'; // Update this path to the OpenSSL executable

        $process = new Process([
            $opensslPath, 'enc', '-aes-256-cbc', '-salt',
            '-in', $backupFile,
            '-out', $encryptedBackupPath,
            '-k', $password
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Encryption failed: ' . $process->getErrorOutput());
            return;
        }

        // Step 4: Upload the encrypted file to Google Drive
        $googleDriveDisk = Storage::disk('google');
        $encryptedFile = new IlluminateFile($encryptedBackupPath);
        $googleDriveDisk->putFileAs('', $encryptedFile, 'encrypted_backup3.zip');

        $this->info('Backup encrypted and uploaded to Google Drive successfully.');

        // Optional Step 5: Clean up local backup files
        // File::delete($backupFile);
        // File::delete($encryptedBackupPath);
    }
}