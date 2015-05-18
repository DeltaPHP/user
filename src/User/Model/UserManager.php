<?php
/**
 * User: Vasiliy Shvakin (orbisnull) zen4dev@gmail.com
 */

namespace User\Model;

use Attach\Model\FileManager;
use DeltaDb\EntityInterface;
use DeltaDb\Repository;
use PermAuth\Model\Authenticator;
use User\Exception\UserAlreadyExists;
use User\Exception\UserNotFound;
use User\Exception\WrongUserCredential;

class UserManager extends Repository
{
    const SESSION_CURRENT_USER = 'uid';
    protected $metaInfo = [
        'users' => [
            'class'  => '\\User\\Model\\User',
            'id'     => 'id',
            'fields' => [
                'id',
                'email',
                'password',
                'group',
                'first_name',
                'last_name',
                'confirmed',
                "created",
                "changed",
            ]
        ]
    ];

    /**
     * @var \HttpWarp\Session
     */
    protected $session;

    /**
     * @var User|null
     */
    protected $currentUser;

    /** @var  Repository */
    protected $groupManager;

    /** @var  FileManager */
    protected $fileManager;

    protected $guest;

    /**
     * @param \HttpWarp\Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return \HttpWarp\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return Repository
     */
    public function getGroupManager()
    {
        return $this->groupManager;
    }

    /**
     * @param Repository $groupManager
     */
    public function setGroupManager($groupManager)
    {
        $this->groupManager = $groupManager;
    }

    /**
     * @param \Attach\Model\FileManager $fileManager
     */
    public function setFileManager($fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @return \Attach\Model\FileManager
     */
    public function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @param array $data
     * @param null $entityClass
     * @return User
     */
    public function create(array $data = null, $entityClass = null)
    {
        $item = parent::create($data, $entityClass);
        $item->setGroupManager($this->getGroupManager());
        $item->setUserManager($this);
        return $item;
    }

    public function reserve(EntityInterface $entity)
    {
        $data = parent::reserve($entity);
        $fields = isset($data["fields"]) ? $data["fields"] : $data;
        if ($fields["group"] && is_object($fields["group"])) {
            $fields["group"] = $fields["group"]->getId();
        }
        return $data;
    }

    public function authenticate($email, $password)
    {
        $table = $this->getTableName("\\User\\Model\\User");
        $adapter = $this->getAdapter();
        $data = $adapter->selectBy($table, ["email" => $email]);
        if (empty($data)) {
            throw new UserNotFound();
        }
        $data = reset($data);
        if (empty($data)) {
            throw new UserNotFound();
        }
        if (!User::verifyPassword($password,$data['password'])) {
            throw new WrongUserCredential();
        }
        return $this->findById($data['id']);
    }

    /**
     * @param $email
     * @return User|null
     */
    public function findByEmail($email)
    {

        return $this->findOne(['email'=>$email]);
    }

    public function setCurrentUser(User $user)
    {
        $session = $this->getSession();
        $session->set(self::SESSION_CURRENT_USER, $user->getId());
    }

    public function getCurrentUser()
    {
        if (is_null($this->currentUser)) {
            $session = $this->getSession();
            if (null !== ($uid = $session->get(self::SESSION_CURRENT_USER))) {
                $this->currentUser = $this->findById($uid);
            }
            if (is_null($this->currentUser)) {
                $this->currentUser = $this->getGuest();
            }
        }
        return $this->currentUser;
    }

    public function isAuth()
    {
        return ! $this->getCurrentUser() instanceof GuestUser;
    }

    public function logout()
    {
        $session = $this->getSession();
        $session->rm(self::SESSION_CURRENT_USER);
    }

    public function addUser($email, $password)
    {
        $someUser = $this->findByEmail($email);
        if ($someUser) {
            throw new UserAlreadyExists();
        }
        /** @var User $user */
        $user = $this->create();
        $user->setEmail($email);
        $user->setNewPassword($password);
        $groups = $this->getGroupManager()->find(['name' =>'user']);
        if (count($groups)) {
            $user->setGroup($groups[0]);
        }
        $this->save($user);
        return $user;
    }

    public function getGuest()
    {
        if (is_null($this->guest)) {
            $this->guest = new GuestUser();
            $this->guest->setGroup(new GuestGroup());
        }
        return $this->guest;
    }

    public function save(EntityInterface $entity)
    {
        /** @var User $entity */
        $created = $entity->getCreated();
        if (is_null($created)) {
            $entity->setCreated(new \DateTime());
        }
        $entity->setChanged(new \DateTime());
        return parent::save($entity);
    }

} 