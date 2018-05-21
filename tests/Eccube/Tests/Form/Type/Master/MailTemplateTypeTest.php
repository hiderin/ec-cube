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

namespace Eccube\Tests\Form\Type\Master;

use Eccube\Form\Type\Master\MailTemplateType;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Tests\Form\Type\AbstractTypeTestCase;

class MailTemplateTypeTest extends AbstractTypeTestCase
{
    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var MailTemplateRepository */
    protected $mailTemplateRepo;

    public function setUp()
    {
        parent::setUp();
        $this->mailTemplateRepo = $this->container->get(MailTemplateRepository::class);

        // CSRF tokenを無効にしてFormを作成
        $this->form = $this->formFactory
            ->createBuilder(MailTemplateType::class, null, [
                'csrf_protection' => false,
            ])
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit(1);
        $this->assertTrue($this->form->isValid());
        $this->assertEquals($this->form->getData(), $this->mailTemplateRepo->find(1));
    }

    public function testViewData()
    {
        $view = $this->form->createView();
        $choices = $view->vars['choices'];

        $data = [];
        foreach ($choices as $choice) {
            $data[] = $choice->data;
        }
        $query = $this->mailTemplateRepo->createQueryBuilder('m')
            ->orderBy('m.id', 'ASC')
            ->getQuery();
        $res = $query->getResult();
        // order by されているか
        $this->assertEquals($data, $res);
    }

    /**
     * 範囲外の値のテスト
     */
    public function testInvalidData_Int()
    {
        $this->form->submit(50);
        $this->assertFalse($this->form->isValid());
    }

    /**
     * 範囲外の値のテスト
     */
    public function testInvalidData_String()
    {
        $this->form->submit('a');
        $this->assertFalse($this->form->isValid());
    }
}
