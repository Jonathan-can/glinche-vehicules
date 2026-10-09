<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreur lors de la communication avec l'API Glinche
 * (identifiants manquants, API injoignable, statut HTTP en erreur, format inattendu).
 */
class GlincheApiException extends RuntimeException
{
}
