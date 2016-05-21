<?php

namespace Youshido\AdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Youshido\AdminBundle\Traits\TimetrackableTrait;

/**
 * AdminUser
 *
 * @ORM\Table(name="admin_user")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class AdminUser implements AdvancedUserInterface, \Serializable
{

    use TimetrackableTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255)
     */
    protected $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    protected $password;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;


    /**
     * @ORM\ManyToMany(targetEntity="AdminRight",cascade={"persist"})
     * @ORM\JoinTable(name="admin_user_roles",
     *      joinColumns={@ORM\JoinColumn(name="id_user", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="id_right", referencedColumnName="id")}
     *      )
     **/
    protected $rights;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set login
     *
     * @param string $login
     * @return AdminUser
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return AdminUser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return AdminUser
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function getRoles()
    {
        $roles = [];
        foreach ($this->getRights() as $right) {
            $roles[] = $right->getId();
        }
        return $roles;
    }


    public function getSalt()
    {
        return "hh";
    }


    public function getUsername()
    {
        return $this->login;
    }


    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }


    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->login,
            $this->password,
            $this->isActive,
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->login,
            $this->password,
            $this->isActive,
            // $this->salt
            ) = unserialize($serialized);
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rights = new ArrayCollection();
    }

    /**
     * Add rights
     *
     * @param AdminRight $rights
     * @return AdminUser
     */
    public function addRight(AdminRight $rights)
    {
        $this->rights[] = $rights;

        return $this;
    }

    /**
     * Set rights
     *
     * @param AdminRight[] $rights
     * @return AdminUser
     */
    public function setRights($rights)
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * Remove rights
     *
     * @param AdminRight $rights
     */
    public function removeRight(AdminRight $rights)
    {
        $this->rights->removeElement($rights);
    }

    /**
     * Get rights
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRights()
    {
        return $this->rights;
    }
}
