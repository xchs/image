<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Imagine\Image\Box;
use PHPUnit\Framework\TestCase;

class PictureGeneratorTest extends TestCase
{
    public function testInstantiation()
    {
        $pictureGenerator = $this->createPictureGenerator();

        $this->assertInstanceOf('Contao\Image\PictureGenerator', $pictureGenerator);
        $this->assertInstanceOf('Contao\Image\PictureGeneratorInterface', $pictureGenerator);
    }

    public function testGenerate()
    {
        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->method('resize')
            ->will($this->returnCallback(
                function (ImageInterface $image, ResizeConfigurationInterface $config) {
                    $imageMock = $this->createMock(Image::class);

                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box(
                            min(1000, $config->getWidth()),
                            min(1000, $config->getHeight())
                        )))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.min(1000, $config->getWidth()).'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.min(1000, $config->getWidth()).'.jpg')
                    ;

                    return $imageMock;
                }
            ))
        ;

        $imageMock = $this->createMock(Image::class);

        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(1000, 1000)))
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setDensities('1x, 1.35354x, 1.9999x, 10x')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setWidth(99)
                    ->setHeight(99)
                )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setMedia('(min-width: 600px)')
                        ->setDensities('0.5x')
                        ->setSizes('50vw')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(50)
                        ),
                    (new PictureConfigurationItem())
                        ->setMedia('(min-width: 300px)')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(50)
                            ->setHeight(25)
                        ),
                    (new PictureConfigurationItem())
                        ->setMedia('(min-width: 200px)')
                        ->setDensities('1x, 10x')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(160)
                            ->setHeight(160)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-99.jpg 1x, image-134.jpg 1.354x, image-198.jpg 2x, image-990.jpg 10x',
                'src' => 'image-99.jpg',
                'width' => 99,
                'height' => 99,
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.jpg 100w, image-50.jpg 50w',
                    'src' => 'image-100.jpg',
                    'width' => 100,
                    'height' => 50,
                    'sizes' => '50vw',
                    'media' => '(min-width: 600px)',
                ],
                [
                    'srcset' => 'image-50.jpg',
                    'src' => 'image-50.jpg',
                    'width' => 50,
                    'height' => 25,
                    'media' => '(min-width: 300px)',
                ],
                [
                    'srcset' => 'image-160.jpg 1x, image-1000.jpg 6.25x',
                    'src' => 'image-160.jpg',
                    'width' => 160,
                    'height' => 160,
                    'media' => '(min-width: 200px)',
                ],
            ],
            $picture->getSources('/root/dir')
        );

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    public function testGenerateWDescriptor()
    {
        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->method('resize')
            ->will($this->returnCallback(
                function (ImageInterface $image, ResizeConfigurationInterface $config) {
                    $imageMock = $this->createMock(Image::class);

                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box($config->getHeight() * 2, $config->getHeight())))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.($config->getHeight() * 2).'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.($config->getHeight() * 2).'.jpg')
                    ;

                    return $imageMock;
                }
            ))
        ;

        $imageMock = $this->createMock(Image::class);

        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(400, 200)))
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setDensities('200w, 400w, 0.5x')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setHeight(100)
                )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('1x, 100w, 200w, 0.5x')
                        ->setSizes('33vw')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(50)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-200.jpg 200w, image-400.jpg 400w, image-100.jpg 100w',
                'src' => 'image-200.jpg',
                'width' => 200,
                'height' => 100,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.jpg 100w, image-200.jpg 200w, image-50.jpg 50w',
                    'src' => 'image-100.jpg',
                    'width' => 100,
                    'height' => 50,
                    'sizes' => '33vw',
                ],
            ],
            $picture->getSources('/root/dir')
        );

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    public function testGenerateWDescriptorSmallImage()
    {
        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->method('resize')
            ->will($this->returnCallback(
                function (ImageInterface $image, ResizeConfigurationInterface $config) {
                    $imageMock = $this->createMock(Image::class);

                    $calculator = new ResizeCalculator();
                    $size = $calculator->calculate($config, new ImageDimensions(new Box(123, 246)))->getCropSize();

                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions($size))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.$size->getWidth().'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.$size->getWidth().'.jpg')
                    ;

                    return $imageMock;
                }
            ))
        ;

        $imageMock = $this->createMock(Image::class);

        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(123, 246)))
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setDensities('100w, 50w, 0.2x, 500w, 2x')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setWidth(200)
                )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('100w, 50w, 0.2x, 500w, 2x')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setHeight(400)
                        ),
                    (new PictureConfigurationItem())
                        ->setDensities('0.5x, 0.25x, 0.2x, 2x')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(200)
                        ),
                    (new PictureConfigurationItem())
                        ->setDensities('100w, 50w, 0.2x, 500w, 2x')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(200)
                            ->setHeight(440)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-123.jpg 123w, image-100.jpg 100w, image-50.jpg 50w, image-40.jpg 40w',
                'src' => 'image-123.jpg',
                'width' => 123,
                'height' => 246,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-123.jpg 123w, image-100.jpg 100w, image-50.jpg 50w, image-40.jpg 40w',
                    'src' => 'image-123.jpg',
                    'width' => 123,
                    'height' => 246,
                    'sizes' => '100vw',
                ],
                [
                    'srcset' => 'image-123.jpg 0.615x, image-100.jpg 0.5x, image-50.jpg 0.25x, image-40.jpg 0.2x',
                    'src' => 'image-123.jpg',
                    'width' => 123,
                    'height' => 246,
                ],
                [
                    'srcset' => 'image-112.jpg 112w, image-100.jpg 100w, image-50.jpg 50w, image-40.jpg 40w',
                    'src' => 'image-112.jpg',
                    'width' => 112,
                    'height' => 246,
                    'sizes' => '100vw',
                ],
            ],
            $picture->getSources('/root/dir')
        );

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    public function testGenerateDuplicateSrcsetItems()
    {
        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->method('resize')
            ->will($this->returnCallback(
                function (ImageInterface $image, ResizeConfigurationInterface $config) {
                    $imageMock = $this->createMock(Image::class);

                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box(min(200, $config->getWidth()), min(200, $config->getHeight()))))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.min(200, $config->getWidth()).'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.min(200, $config->getWidth()).'.jpg')
                    ;

                    return $imageMock;
                }
            ))
        ;

        $imageMock = $this->createMock(Image::class);

        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setDensities('200w, 400w, 600w, 3x, 4x, 0.5x')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setWidth(100)
                    ->setHeight(100)
                )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('1x, 2x, 3x, 4x, 0.5x')
                        ->setResizeConfig((new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(50)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-100.jpg 100w, image-200.jpg 200w, image-50.jpg 50w',
                'src' => 'image-100.jpg',
                'width' => 100,
                'height' => 100,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.jpg 1x, image-200.jpg 2x, image-50.jpg 0.5x',
                    'src' => 'image-100.jpg',
                    'width' => 100,
                    'height' => 50,
                ],
            ],
            $picture->getSources('/root/dir')
        );

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    public function testGenerateWithLocale()
    {
        $locale = setlocale(LC_NUMERIC, 0);

        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        try {
            $requiredLocales = ['de_DE.UTF-8', 'de_DE.UTF8', 'de_DE.utf-8', 'de_DE.utf8', 'German_Germany.1252'];
            if (false === setlocale(LC_NUMERIC, $requiredLocales)) {
                $this->markTestSkipped('Could not set any of required locales: '.implode(', ', $requiredLocales));
            }
            $this->testGenerate();
        } finally {
            setlocale(LC_NUMERIC, $locale);
        }
    }

    /**
     * Returns a picture generator.
     *
     * @param ResizerInterface $resizer
     *
     * @return PictureGenerator
     */
    private function createPictureGenerator($resizer = null)
    {
        if (null === $resizer) {
            $resizer = $this->createMock(ResizerInterface::class);
        }

        return new PictureGenerator($resizer);
    }
}
