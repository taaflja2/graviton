<?php
/**
 * airlock authkey based consultant user provider
 */

namespace Graviton\SecurityBundle\Authentication\Provider;

use \Graviton\RestBundle\Model\ModelInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class AirlockAuthenticationKeyConsultantProvider
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class AuthenticationProvider implements UserProviderInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModelInterface
     */
    protected $documentModel;

    /**
     * @var String
     */
    protected $queryField;

    /**
     * @param LoggerInterface $logger     logger
     * @param ModelInterface  $model      The documentModel
     * @param string          $queryField Field to search by
     */
    public function __construct(LoggerInterface $logger, ModelInterface $model, $queryField)
    {
        $this->logger = $logger;
        $this->documentModel = $model;
        $this->queryField = $queryField;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username the consultants username
     *
     * @return false|UserInterface
     */
    public function loadUserByUsername($username)
    {
        if (!$this->queryField || !$username) {
            return false;
        }

        $critera = [
            $this->queryField => new \MongoRegex('/^'.preg_quote($username).'$/i')
        ];

        $this->logger->info('Searching for user', ['criteria' => $critera]);

        $user = $this->documentModel->getRepository()->findOneBy($critera);

        $this->logger->info('Found user', ['user' => $user]);

        return $user ? $user : false;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user user to refresh
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class class to check for support
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class instanceof UserInterface;
    }
}
