<?php

namespace App\Policies;

use App\Models\Knowledge\KnowledgeArticle;
use App\Models\User;

/**
 * Controla acesso a artigos da EasyWiki — previne IDOR (OWASP A01).
 *
 * Regras:
 *  - delete: admin ou autor do artigo.
 */
class KnowledgeArticlePolicy
{
    public function delete(User $user, KnowledgeArticle $article): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAgent() && (int) $article->author_id === $user->id;
    }
}
