<?php

declare(strict_types=1);

namespace App\Exception\Domain;

/**
 * Base exception for domain/business rule violations.
 *
 * This exception should be used when an action is technically valid
 * but forbidden by a business rule.
 *
 * Examples:
 * - publishing an archived document;
 * - scheduling a campaign without documents;
 * - accessing a resource from another organization.
 */
class BusinessRuleException extends \DomainException
{
}