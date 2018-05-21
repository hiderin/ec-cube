<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Eccube\Controller\Admin\Setting\Shop;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CsvType;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CsvController
 */
class CsvController extends AbstractController
{
    /**
     * @var CsvRepository
     */
    protected $csvRepository;

    /**
     * @var CsvTypeRepository
     */
    protected $csvTypeRepository;

    /**
     * CsvController constructor.
     *
     * @param CsvRepository $csvRepository
     * @param CsvTypeRepository $csvTypeRepository
     */
    public function __construct(CsvRepository $csvRepository, CsvTypeRepository $csvTypeRepository)
    {
        $this->csvRepository = $csvRepository;
        $this->csvTypeRepository = $csvTypeRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/setting/shop/csv/{id}",
     *     requirements={"id" = "\d+"},
     *     defaults={"id" = CsvType::CSV_TYPE_ORDER},
     *     name="admin_setting_shop_csv"
     * )
     * @Template("@admin/Setting/Shop/csv.twig")
     */
    public function index(Request $request, CsvType $CsvType)
    {
        $builder = $this->createFormBuilder();

        $builder->add(
            'csv_type',
            \Eccube\Form\Type\Master\CsvType::class,
            [
                'label' => 'CSV出力項目',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'data' => $CsvType,
            ]
        );

        $CsvNotOutput = $this->csvRepository->findBy(
            ['CsvType' => $CsvType, 'enabled' => false],
            ['sort_no' => 'ASC']
        );

        $builder->add(
            'csv_not_output',
            EntityType::class,
            [
                'class' => 'Eccube\Entity\Csv',
                'choice_label' => 'disp_name',
                'required' => false,
                'expanded' => false,
                'multiple' => true,
                'choices' => $CsvNotOutput,
            ]
        );

        $CsvOutput = $this->csvRepository->findBy(
            ['CsvType' => $CsvType, 'enabled' => true],
            ['sort_no' => 'ASC']
        );

        $builder->add(
            'csv_output',
            EntityType::class,
            [
                'class' => 'Eccube\Entity\Csv',
                'choice_label' => 'disp_name',
                'required' => false,
                'expanded' => false,
                'multiple' => true,
                'choices' => $CsvOutput,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'CsvOutput' => $CsvOutput,
                'CsvType' => $CsvType,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_SETTING_SHOP_CSV_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $data = $request->get('form');
            if (isset($data['csv_not_output'])) {
                $Csvs = $data['csv_not_output'];
                $sortNo = 1;
                foreach ($Csvs as $csv) {
                    $c = $this->csvRepository->find($csv);
                    $c->setSortNo($sortNo);
                    $c->setEnabled(false);
                    $sortNo++;
                }
            }

            if (isset($data['csv_output'])) {
                $Csvs = $data['csv_output'];
                $sortNo = 1;
                foreach ($Csvs as $csv) {
                    $c = $this->csvRepository->find($csv);
                    $c->setSortNo($sortNo);
                    $c->setEnabled(true);
                    $sortNo++;
                }
            }

            $this->entityManager->flush();

            $event = new EventArgs(
                [
                    'form' => $form,
                    'CsvOutput' => $CsvOutput,
                    'CsvType' => $CsvType,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_SETTING_SHOP_CSV_INDEX_COMPLETE, $event);

            $this->addSuccess('admin.shop.csv.save.complete', 'admin');

            return $this->redirectToRoute('admin_setting_shop_csv', ['id' => $CsvType->getId()]);
        }

        return [
            'form' => $form->createView(),
            'id' => $CsvType->getId(),
        ];
    }
}
