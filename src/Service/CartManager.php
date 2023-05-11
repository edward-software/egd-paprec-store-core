<?php
/**
 * Created by PhpStorm.
 * User: frede
 * Date: 13/11/2018
 * Time: 11:38
 */

namespace App\Service;


use App\Entity\Cart;
use App\Entity\OtherNeed;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;


class CartManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Retourne un Cart en passant son Id ou un object Cart
     * @param $cart
     * @return null|object|Cart
     * @throws Exception
     */
    public function get($cart)
    {
        $id = $cart;
        if ($cart instanceof Cart) {
            $id = $cart->getId();
        }
        try {

            $cart = $this->em->getRepository('App:Cart')->find($id);

            if ($cart === null || $this->isDisabled($cart)) {
                throw new EntityNotFoundException('cartNotFound', 404);
            }

            return $cart;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérification qu'a ce jour, le cart ne soit pas désactivé
     *
     * @param Cart $cart
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDisabled(Cart $cart, $throwException = false)
    {
        $now = new \DateTime();

        if ($cart->getDisabled() !== null && $cart->getDisabled() instanceof \DateTime && $cart->getDisabled() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('cartNotFound');
            }

            return true;

        }
        return false;
    }

    /**
     * Créé un nouveau Cart en initialisant sa date Disabled  dans Today + $deltaJours
     *
     * @param $deltaJours
     * @return Cart
     * @throws Exception
     */
    public function create($deltaJours)
    {
        try {

            $cart = new Cart();

            /**
             * Initialisant de $disabled
             */
            $now = new \DateTime();
            $disabledDate = $now->modify('+' . $deltaJours . 'day');
            $cart->setDisabled($disabledDate);


            $this->em->persist($cart);
            $this->em->flush();

            return $cart;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Ajoute du content au cart pour un produit et une quantité donnée
     *
     * @param $id
     * @param $productId
     * @param $quantity
     * @return mixed
     * @throws Exception
     */
    public function addContent($id, Product $product, $quantity)
    {

        $cart = $this->get($id);

        $content = $cart->getContent();
        $newContent = [
            'pId' => $product->getId(),
            'qtty' => $quantity,
            'frequency' => $product->getFrequency(),
            'frequencyTimes' => $product->getFrequencyTimes(),
            'frequencyInterval' => $product->getFrequencyInterval()
        ];
        if ($content && count($content)) {
            foreach ($content as $key => $value) {
                if ($value['pId'] == $product->getId()) {
                    unset($content[$key]);
                }
            }
        }

        $content[] = $newContent;
        $cart->setContent($content);
        $this->em->flush();
        return $cart;
    }


    /**
     * Supprime un produit
     * @param $id
     * @param $productId
     * @return null|object|Cart
     * @throws Exception
     */
    public function removeContent($id, $productId)
    {
        $cart = $this->get($id);
        $products = $cart->getContent();
        if ($products && count($products)) {
            foreach ($products as $key => $product) {
                if ($product['pId'] == $productId) {
                    unset($products[$key]);
                }
            }
        }
        $cart->setContent($products);
        $this->em->flush();
        return $cart;
    }


    /**
     *
     * @param $id
     * @param $frequency
     * @param $frequencyTimes
     * @param $frequencyInterval
     * @throws Exception
     */
    public function addFrequency($id, $frequency, $frequencyTimes, $frequencyInterval)
    {
        $cart = $this->get($id);

        if (strtoupper($frequency) === 'REGULAR' || strtoupper($frequency) === 'PONCTUAL') {
            $cart->setFrequency(strtoupper($frequency));
            $cart->setFrequencyTimes($frequencyTimes);
            $cart->setFrequencyInterval($frequencyInterval);
            $this->em->flush();
        } else {
            throw new Exception('frequency_invalid');
        }
    }


    /**
     * Ajoute du content au cart pour un produit
     *
     * @param $id
     * @param $productId
     * @return string
     * @throws Exception
     */
    public function addOneProduct($id, Product $product)
    {
        $cart = $this->get($id);
        $qtty = '1';
        $content = $cart->getContent();
        /**
         * Si la ligne existe on réutilise sa fréquence sinon on utilise sa fréquence par défaut
         */
        $frequencyTimes = 5;
        $frequencyInterval = 'WEEK';
        if ($content && count($content)) {
            foreach ($content as $key => $prod) {
                if ($prod['pId'] == $product->getId()) {
                    $qtty = (string)((int)$prod['qtty'] + 1);
                    unset($content[$key]);
                    $frequencyTimes = (int)$prod['frequencyTimes'];
                    $frequencyInterval = $prod['frequencyInterval'];
                }
            }
        }


        /**
         * Si la ligne n'existe pas on utilise la fréquence par défaut fréquence
         */

        $newContent = [
            'pId' => $product->getId(),
            'qtty' => $qtty,
            'frequency' => $product->getFrequency(),
            'frequencyTimes' => $frequencyTimes,
            'frequencyInterval' => $frequencyInterval
        ];
        $content[] = $newContent;

        $cart->setContent($content);
        $this->em->persist($cart);
        $this->em->flush();
        return $qtty;
    }

    /**
     * Elève 1 de de quantité au cart pour un produit
     *
     * @param $id
     * @param $productId
     * @param $quantity
     * @return string
     * @throws Exception
     */
    public function removeOneProduct($id, Product $product)
    {
        try {

            $cart = $this->get($id);
            $qtty = '0';
            $content = $cart->getContent();
            if ($content && count($content)) {
                foreach ($content as $key => $prod) {
                    if ($prod['pId'] == $product->getId()) {
                        $qtty = (string)((int)$prod['qtty'] - 1);
                        unset($content[$key]);
                    }
                }
            }

            if ($qtty !== '0') {
                $newContent = [
                    'pId' => $product->getId(),
                    'qtty' => $qtty,
                    'frequency' => $product->getFrequency(),
                    'frequencyTimes' => $product->getFrequencyTimes(),
                    'frequencyInterval' => $product->getFrequencyInterval()
                ];
                $content[] = $newContent;
            }

            $cart->setContent($content);
            $this->em->persist($cart);
            $this->em->flush();

            return $qtty;
        } catch (Exception $e) {
        }
    }


    /**
     * Ajout d'un OtherNeed au cart
     *
     * @param $id
     * @param $otherNeed
     * @return object|Cart|null
     */
    public function addOrRemoveOtherNeed($id, OtherNeed $otherNeed)
    {
        try {
            $cart = $this->get($id);
            $cartOtherNeeds = $cart->getOtherNeeds();

            $found = false;

            if ($cartOtherNeeds && count($cartOtherNeeds)) {
                foreach ($cartOtherNeeds as $cartOtherNeed) {
                    if ($cartOtherNeed->getId() === $otherNeed->getId()) {
                        $found = true;
                    }
                }
            }

            if ($found) {
                $cart->removeOtherNeed($otherNeed);
                $otherNeed->removeCart($cart);

            } else {
                $cart = $cart->addOtherNeed($otherNeed);
                $otherNeed->addCart($cart);
            }

            $this->em->flush();

            return $cart;
        } catch (Exception $e) {
        }
    }

    /**
     * Suppression d'un OtherNeed au cart
     * @param $id
     * @param $otherNeed
     * @return object|Cart|null
     */
    public function removeOtherNeed($id, $otherNeed)
    {
        try {
            $cart = $this->get($id);

            $cart->removeOtherNeed($otherNeed);

            $this->em->flush();

            return $cart;
        } catch (Exception $e) {
        }
    }


    /**
     * Edit les informations de fréquence d'un produit ajouté au panier
     *
     * @param $cartUuid
     * @param Product $product
     * @param $frequency
     * @param $frequencyTimes
     * @param $frequencyInterval
     */
    public function editProductFrequency($cartUuid, Product $product, $frequency, $frequencyTimes, $frequencyInterval)
    {
        $cart = $this->get($cartUuid);

        $qtty = 1;
        $content = $cart->getContent();
        if ($content && count($content)) {
            foreach ($content as $key => $prod) {
                if ($prod['pId'] == $product->getId()) {
                    $qtty = (string)((int)$prod['qtty']);
                    unset($content[$key]);
                }
            }
        }
        $newContent = [
            'pId' => $product->getId(),
            'qtty' => $qtty,
            'frequency' => $frequency,
            'frequencyTimes' => $frequencyTimes,
            'frequencyInterval' => $frequencyInterval
        ];
        $content[] = $newContent;

        $cart->setContent($content);
        $this->em->persist($cart);
        $this->em->flush();
        return $qtty;
    }

    /**
     * Mise à jour de la date de collecte souhaitée d'un cart ponctual
     *
     * @param $cartUuid
     * @param $ponctualDate
     * @return Cart|object|null
     * @throws Exception
     */
    public function editPonctualDate($cartUuid, $ponctualDate)
    {
        $cart = $this->get($cartUuid);

        $cart->setPonctualDate($ponctualDate);
        $this->em->persist($cart);
        $this->em->flush();

        return $cart;
    }
}
