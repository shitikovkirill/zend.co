<?php
namespace MyEconomic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="other_direct_costs")
 */
class OtherDirectCosts
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="MyUser\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(name ="year", type="string", length=255)
     */
    protected $year;

    /**
     * @var string
     * @ORM\Column(name ="direct_costs", type="string", length=255)
     */
    protected $otherDirectCosts;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param string $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function getOtherDirectCosts()
    {
        return $this->otherDirectCosts;
    }

    /**
     * @param string $turnover
     */
    public function setOtherDirectCosts($otherDirectCosts)
    {
        $this->otherDirectCosts = $otherDirectCosts;
    }


}
