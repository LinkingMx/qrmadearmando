<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\User;
use App\Services\UserImportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UsersImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsOnError
{
    use SkipsErrors;

    protected UserImportService $importService;
    protected array $importErrors = [];
    protected array $created = [];
    protected array $updated = [];
    protected bool $updateExisting;

    public function __construct(UserImportService $importService, bool $updateExisting = false)
    {
        $this->importService = $importService;
        $this->updateExisting = $updateExisting;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is headers and Excel is 1-indexed

            try {
                // Validate required fields
                if (empty($row['nombre']) || empty($row['email'])) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'Nombre y email son requeridos',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Validate email format
                if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'Email inválido',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Check if user exists
                $existingUser = User::withTrashed()->where('email', $row['email'])->first();

                if ($existingUser && !$this->updateExisting) {
                    $this->importErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'El email ya existe',
                        'data' => $row->toArray(),
                    ];
                    continue;
                }

                // Find branch if specified
                $branchId = null;
                if (!empty($row['sucursal'])) {
                    $branch = Branch::where('name', $row['sucursal'])
                        ->orWhere('id', $row['sucursal'])
                        ->first();

                    if (!$branch) {
                        $this->importErrors[] = [
                            'row' => $rowNumber,
                            'error' => "Sucursal '{$row['sucursal']}' no encontrada",
                            'data' => $row->toArray(),
                        ];
                        continue;
                    }

                    $branchId = $branch->id;
                }

                // Handle password
                $password = null;
                if (!empty($row['contrasena'])) {
                    $password = Hash::make($row['contrasena']);
                } elseif (!$existingUser) {
                    // Generate random password for new users
                    $generatedPassword = Str::random(12);
                    $password = Hash::make($generatedPassword);
                    $row['generated_password'] = $generatedPassword; // Store for report
                }

                // Handle photo
                $avatarPath = null;
                if (!empty($row['foto'])) {
                    $photoPath = $this->importService->findPhotoForUser(
                        $row['foto'],
                        $row['email'],
                        $row['nombre']
                    );

                    if ($photoPath) {
                        $avatarPath = $this->importService->storeAvatar($photoPath, $row['email']);
                    }
                }

                // Create or update user
                $userData = [
                    'name' => $row['nombre'],
                    'email' => $row['email'],
                    'branch_id' => $branchId,
                ];

                if ($avatarPath) {
                    $userData['avatar'] = $avatarPath;
                }

                if ($password) {
                    $userData['password'] = $password;
                }

                if ($existingUser) {
                    // Restore if soft deleted
                    if ($existingUser->trashed()) {
                        $existingUser->restore();
                    }

                    // Update existing user
                    $existingUser->update($userData);
                    $this->updated[] = [
                        'row' => $rowNumber,
                        'email' => $row['email'],
                        'name' => $row['nombre'],
                    ];
                } else {
                    // Create new user
                    $user = User::create($userData);
                    $this->created[] = [
                        'row' => $rowNumber,
                        'email' => $row['email'],
                        'name' => $row['nombre'],
                        'password' => $row['generated_password'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                $this->importErrors[] = [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray(),
                ];
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function getCreated(): array
    {
        return $this->created;
    }

    public function getUpdated(): array
    {
        return $this->updated;
    }

    public function getStats(): array
    {
        return [
            'created' => count($this->created),
            'updated' => count($this->updated),
            'errors' => count($this->importErrors),
            'total' => count($this->created) + count($this->updated) + count($this->importErrors),
        ];
    }
}
