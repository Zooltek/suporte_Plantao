<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\ProfileRepositoryInterface;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $repository,
    ) {}

    /**
     * Atualiza os dados básicos e o avatar do usuário.
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $file = null): bool
    {
        $payload = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if ($file) {
            $payload['avatar'] = $this->uploadAvatar($user->id, $file);
        } elseif (($data['remove'] ?? 0) == 1) {
            $payload['avatar'] = 'null.png';
        }

        return $this->repository->updateProfile($user, $payload);
    }

    /**
     * Gerencia o upload do avatar.
     */
    private function uploadAvatar(int $userId, UploadedFile $file): string
    {
        // A03/OWASP: usa $file->extension() (detectado pelo Symfony via finfo a partir
        // do conteúdo real do arquivo) — não confia na extensão informada pelo cliente.
        $ext      = $file->extension();
        $filename = "user_{$userId}_" . time() . '.' . $ext;
        $file->move(public_path('img/user'), $filename);

        return $filename;
    }

    /**
     * Altera a senha validando a anterior.
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): array
    {
        if (!Hash::check($oldPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'A senha atual inserida não é válida!',
            ];
        }

        $this->repository->updatePassword($user, Hash::make($newPassword));

        return [
            'success' => true,
            'message' => 'Senha alterada com sucesso!',
        ];
    }
}
