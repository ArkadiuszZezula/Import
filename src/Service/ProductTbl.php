<?php

namespace App\Service;

use App\Entity\Tblproductdata;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;


class ProductTbl
{
    /**
     * Validate errors
     * @var array
     */
    private $errors = [];

    /**
     * Product data
     * @var array
     */
    private $product = [];

    /**
     * ProductTbl constructor.
     * @param array $product
     */
    public function __construct(array $product)
    {
        $this->product = $product;
    }

    /**
     * Parse data to Tblproductdata object
     * @return Tblproductdata
     */
    public function parse()
    {
        $tblProductdata = new Tblproductdata();
        if (isset($this->product['Product Name'])) {
            $tblProductdata->setStrproductname($this->product['Product Name']);
        }
        if (isset($this->product['Product Description'])) {
            $tblProductdata->setStrproductdesc($this->product['Product Description']);
        }
        if (isset($this->product['Product Code'])) {
            $tblProductdata->setStrproductcode($this->product['Product Code']);
        }
        $tblProductdata->setDtmadded(new \DateTime);
        if (isset($this->product['Discontinued'])
            && $this->product['Discontinued'] == strtolower('yes')
        ) {
            $tblProductdata->setDtmdiscontinued(new \DateTime);
        }
        $tblProductdata->setStmtimestamp(
            new \DateTime('@' . strtotime('now'))
        );
        if (isset($this->product['Stock'])) {
            $tblProductdata->setIntstocklevel($this->product['Stock']);
        }
        if (isset($this->product['Discontinued'])) {
            $tblProductdata->setDecPrice($this->product['Cost in GBP']);
        }
        return $tblProductdata;
    }

    /**
     * Check if product can be imported by rules
     * @return bool
     */
    public function isImported()
    {
        if (isset($this->product['Cost in GBP'])
            && $this->product['Cost in GBP'] < 5
            && isset($this->product['Stock'])
            && $this->product['Stock'] < 10
        ) {
            return false;
        }
        if(isset($this->product['Cost in GBP'])
            && $this->product['Cost in GBP'] > 1000
        ){
            return false;
        }
        return true;
    }

    /**
     * Validate imported data
     * @return array Errors
     */
    public function validate()
    {
        $validator = Validation::createValidator();
        $errors = [];

        if (isset($this->product['Product Code'])) {
            $errors['Product Code'] = $validator
                ->validate(
                    $this->product['Product Code'],
                    [
                        new Type('string'),
                        new Length(['max' => '10'])
                    ]
                )->count();
        }

        if (isset($this->product['Product Name'])) {
            $errors['Product Name'] = $validator
                ->validate(
                    $this->product['Product Name'],
                    [
                        new Type('string'),
                        new Length(['max' => '50'])
                    ]
                )->count();
        }

        if (isset($this->product['Product Description'])) {
            $errors['Product Description'] = $validator
                ->validate(
                    $this->product['Product Description'],
                    [
                        new Type('string'),
                        new Length(['max' => '255'])
                    ]
                )->count();
        }

        if (isset($this->product['Stock'])) {
            $errors['Stock'] = $validator
                ->validate(
                    $this->product['Stock'],
                    [
                        new Type('numeric'),
                        new Length(['max' => '11'])
                    ]
                )->count();
        }

        if (isset($this->product['Cost in GBP'])) {
            $errors['Cost in GBP'] = $validator
                ->validate(
                    $this->product['Cost in GBP'],
                    [
                        new Type('numeric'),
                        new Length(['max' => '13'])
                    ]
                )->count();
        }

        if (isset($this->product['Discontinued'])) {
            $errors['Discontinued'] = $validator
                ->validate(
                    $this->product['Discontinued'],
                    [
                        new Choice(['yes', 'no', ''])
                    ]
                )->count();
        }

        foreach ($errors as $key => $error) {
            if ($error > 0) {
                $this->errors[] = $key;
            }
        }
        return $this->errors;
    }


}