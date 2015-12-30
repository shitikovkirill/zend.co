<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 27.12.15
 * Time: 1:18
 */

namespace MyUser\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class UserModel
{
    private $entityManager;
    public function __construct( EntityManager $entityManager){
        $this->entityManager=$entityManager;
    }

    public function checkUsersName($name, $id = null){
        $sql = "SELECT EXISTS (SELECT * FROM users WHERE username = '{$name}' ";
        if(isset($id)){
            $sql .= 'AND id != '.$id;
        }
        $sql.=');';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $usernames = $stmt->fetch();
        $value = each($usernames);

        return $value['value'];
    }

    public function checkUsersEmail($email, $id = null){
        $sql = "SELECT EXISTS (SELECT * FROM users WHERE email = '{$email}' ";
        if(isset($id)){
            $sql .= 'AND id != '.$id;
        }
        $sql.=');';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $usernames = $stmt->fetch();
        $value = each($usernames);

        return $value['value'];
    }

    public function addNewUser($user){
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function getRoles($array){
        $arrayColl = new ArrayCollection();
                foreach($array as $val){
                    $arrayColl->add($this->getRole($val)) ;
                }
        return $arrayColl;
    }

    public function getRole($id){
        $role = $this->entityManager->find('MyUser\Entity\Role', $id);
        return $role;
    }

    public function getUserById($id){
        $user = $this
            ->entityManager
            ->getRepository('MyUser\Entity\User')
            ->find($id);
        return $user;
    }
}