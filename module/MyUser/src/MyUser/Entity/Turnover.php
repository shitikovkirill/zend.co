<?php
namespace MyUser\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="turnover")
 */
class Turnover
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="MyUser\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */    
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $year;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $jan;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $feb;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $mar;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $apr;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $may;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $jun;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $jul;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $aug;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $sep;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $oct;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $nov;

    /**
     * @var decimal
     * @ORM\Column(type="decimal", precision=20, scale=5)
     */
    protected $dec;

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
     * @return decimal
     */
    public function getJan()
    {
        return $this->jan;
    }

    /**
     * @param decimal $jan
     */
    public function setJan($jan)
    {
        $this->jan = $jan;
    }

    /**
     * @return decimal
     */
    public function getFeb()
    {
        return $this->feb;
    }

    /**
     * @param decimal $feb
     */
    public function setFeb($feb)
    {
        $this->feb = $feb;
    }

    /**
     * @return decimal
     */
    public function getMar()
    {
        return $this->mar;
    }

    /**
     * @param decimal $mar
     */
    public function setMar($mar)
    {
        $this->mar = $mar;
    }

    /**
     * @return decimal
     */
    public function getApr()
    {
        return $this->apr;
    }

    /**
     * @param decimal $apr
     */
    public function setApr($apr)
    {
        $this->apr = $apr;
    }

    /**
     * @return decimal
     */
    public function getMay()
    {
        return $this->may;
    }

    /**
     * @param decimal $may
     */
    public function setMay($may)
    {
        $this->may = $may;
    }

    /**
     * @return decimal
     */
    public function getJun()
    {
        return $this->jun;
    }

    /**
     * @param decimal $jun
     */
    public function setJun($jun)
    {
        $this->jun = $jun;
    }

    /**
     * @return decimal
     */
    public function getJul()
    {
        return $this->jul;
    }

    /**
     * @param decimal $jul
     */
    public function setJul($jul)
    {
        $this->jul = $jul;
    }

    /**
     * @return decimal
     */
    public function getAug()
    {
        return $this->aug;
    }

    /**
     * @param decimal $aug
     */
    public function setAug($aug)
    {
        $this->aug = $aug;
    }

    /**
     * @return decimal
     */
    public function getSep()
    {
        return $this->sep;
    }

    /**
     * @param decimal $sep
     */
    public function setSep($sep)
    {
        $this->sep = $sep;
    }

    /**
     * @return decimal
     */
    public function getOct()
    {
        return $this->oct;
    }

    /**
     * @param decimal $oct
     */
    public function setOct($oct)
    {
        $this->oct = $oct;
    }

    /**
     * @return decimal
     */
    public function getNov()
    {
        return $this->nov;
    }

    /**
     * @param decimal $nov
     */
    public function setNov($nov)
    {
        $this->nov = $nov;
    }

    /**
     * @return decimal
     */
    public function getDec()
    {
        return $this->dec;
    }

    /**
     * @param decimal $dec
     */
    public function setDec($dec)
    {
        $this->dec = $dec;
    }
}
