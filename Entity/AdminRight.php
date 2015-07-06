<?php

namespace Youshido\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdminUserRight
 *
 * @ORM\Table(name="AdminRight")
 * @ORM\Entity
 */
class AdminRight {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return AdminRight
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdminRight
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

}
