<?php
/**
 * \Elabftw\Elabftw\FormKey
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Defuse\Crypto\Key;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Prevent CSRF attacks
 */
class FormKey
{
    /** @var Session $Session the session object */
    private $Session;

    /**
     * We need the Session object
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->Session = $session;
        if (!$this->Session->has('csrf')) {
            $this->Session->set('csrf', $this->generate());
        }
    }

    /**
     * Generate a CSRF token
     *
     * @return string
     */
    private function generate(): string
    {
        return Key::createNewRandomKey()->saveToAsciiSafeString();
    }

    /**
     * Return the form key for inclusion in HTML
     *
     * @return string
     */
    public function getHiddenInput(): string
    {
        return "<input type='hidden' name='csrf' value='" . $this->Session->get('csrf') . "' />";
    }

    /**
     * Generate data attribute with csrf token
     *
     * @return string
     */
    public function getDataAttr(): string
    {
        return "data-csrf='" . $this->Session->get('csrf') . "'";
    }

    /**
     * Validate the form key against the one previously set in Session
     *
     * @param string $value
     * @return bool True if there is no CSRF going on (hopefully)
     */
    public function validate(string $value): bool
    {
        return $value === $this->Session->get('csrf');
    }
}
