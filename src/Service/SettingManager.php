<?php
/**
 * Created by PhpStorm.
 * User: frede
 * Date: 30/11/2018
 * Time: 16:42
 */

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use App\Entity\Setting;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingManager
{

    private $em;
    private $container;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->container = $container;
        $this->translator = $translator;
    }

    public function get($setting)
    {
        $id = $setting;
        if ($setting instanceof Setting) {
            $id = $setting->getId();
        }
        try {

            $setting = $this->em->getRepository('App:Setting')->find($id);

            if ($setting === null || $this->isDeleted($setting)) {
                throw new EntityNotFoundException('settingNotFound');
            }

            return $setting;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getByKey(
        $key,
        $returnException = true
    ) {
        try {

            $setting = $this->em->getRepository(Setting::class)->findOneBy([
                'key' => $key,
            ]);

            if ($setting === null || $this->isDeleted($setting)) {
                if ($returnException) {
                    throw new EntityNotFoundException('settingNotFound');
                }
                return null;
            }

            return $setting;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }

    /**
     * Vérification qu'à ce jour le setting n'est pas supprimé
     *
     * @param Setting $setting
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(Setting $setting, $throwException = false)
    {
        $now = new \DateTime();

        if ($setting->getDeleted() !== null && $setting->getDeleted() instanceof \DateTime && $setting->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('settingNotFound');
            }
            return true;
        }
        return false;
    }

}
