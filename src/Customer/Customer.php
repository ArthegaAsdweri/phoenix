<?php

namespace PhoenixPhp\Customer;

use PhoenixPhp\Core\Session;

class Customer
{

    //---- MEMBERS

    private static self $instance;
    private CustomerAddress $address;
    private string $phone;
    private string $email;


    //---- CONSTRUCTOR

    private function __construct()
    {
    }

    /**
     * returns the instance
     * @return self
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            $session = Session::getInstance();

            /** @var Customer $customerClass */
            $customerClass = $session->retrieve('CLASSES', 'CUSTOMER');
            if ($customerClass !== null) {
                return $customerClass;
            } else {
                self::$instance = new self();
                $session->put('CLASSES', 'CUSTOMER', self::$instance);
            }
        }
        return self::$instance;
    }

    function __destruct()
    {
        if(isset(self::$instance)) {
            $session = Session::getInstance();
            $session->put('CLASSES', 'CUSTOMER', self::$instance);
        }
    }

    //---- SETTER & GETTER

    /**
     * @return CustomerAddress
     */
    public function getAddress(): CustomerAddress
    {
        return $this->address;
    }

    /**
     * @param CustomerAddress $address
     */
    public function setAddress(CustomerAddress $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}