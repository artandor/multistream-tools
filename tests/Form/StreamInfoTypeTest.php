<?php

namespace App\Tests\Form;

use App\Entity\TitleHistory;
use App\Entity\User;
use App\Form\StreamInfoType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Security;

class StreamInfoTypeTest extends TypeTestCase
{
    private Security $security;

    public function testSubmitValidData()
    {
        $formData = [
            'title' => 'A crazy good stream',
            'category' => 'League Of legends',
        ];

        $model = new TitleHistory();
        // $model will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(StreamInfoType::class, $model);

        $expected = new TitleHistory();
        $expected->setTitle('A crazy good stream');
        $expected->setCategory('League Of legends');

        $form->submit($formData);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());

        // check that $model was modified as expected when the form was submitted
        $this->assertEquals($expected->getTitle(), $model->getTitle());
        $this->assertEquals($expected->getCategory(), $model->getCategory());
    }

    public function testCustomFormView()
    {
        $formData = new TitleHistory();

        $view = $this->factory->create(StreamInfoType::class, $formData)
            ->createView();

        $this->assertNotNull($view);
    }

    protected function setUp(): void
    {
        $user = new User();
        $this->security = $this->createMock(Security::class);
        $this->security->method('getUser')->willReturn($user);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new StreamInfoType($this->security);

        return [
            new PreloadedExtension([$type], []),
        ];
    }
}
