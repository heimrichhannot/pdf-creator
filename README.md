# PDF Creator

PdfCreator is a high level API for PDF file creating with PHP. Its goal is to make usage of existing libraries easier and object-orientated.

## Features

- supported libraries:
  - [mPDF](https://github.com/mpdf/mpdf)
  - [TCPDF](https://github.com/tecnickcom/TCPDF)

## Example

```php
use HeimrichHannot\UtilsBundle\PdfCreator\PdfCreatorFactory;
use HeimrichHannot\UtilsBundle\PdfCreator\Concrete\MpdfCreator;

$pdf = PdfCreatorFactory::createInstance(MpdfCreator::getType());
$pdf->setHtmlContent($this->compile())
    ->setFilename($this->getFileName())
    ->setFormat('A4')
    ->setOrientation($pdf::ORIENTATION_PORTRAIT)
    ->addFont(
        "/path_to_project/assets/fonts/my_great_font.tff", 
        "myGreatFont", 
        $pdf::FONT_STYLE_REGUALAR,
        "normal"
    )
    ->setMargins(15, 10, 15,10)
    ->setTemplateFilePath("/path_to_project/assets/pdf/mastertemplate.pdf")
    ->setOutputMode($pdf::OUTPUT_MODE_DOWNLOAD)
    ->render()
;
```

## Usage

### Install

We recommend installing this library with composer:

    composer require heimrichhannot/pdf-creator

You also need to install the pdf library, you want to use this bundle with:
- mPDF
  - `"mpdf/mpdf": "^8.0"`
- TCPDF
  - `"tecnickcom/tcpdf": "^6.3"`
  - if you want to use master templates in TCPDF, you also need FPDI:    
    `"setasign/fpdi": "^2.3"`

If you're using [Contao](https://contao.org/), you could try the [PDF Creator Bundle](https://github.com/heimrichhannot/contao-pdf-creator-bundle), which is based on this library.


### Use callback for custom adjustments

Due the high level approach not all specific library functionality could be supported. To add specific configuration, you use the callback mechanism comes with this api.

Callback | Description
-------- | -----------
BeforeCreateLibraryInstanceCallback | Is evaluated before the library instance is created and allows to modifiy the constructor parameters.
BeforeOutputPdfCallback | Is evaluated before the library method to output the pdf is called and provide the library instance and the output method parameters.

```php
use HeimrichHannot\UtilsBundle\PdfCreator\BeforeCreateLibraryInstanceCallback;
use HeimrichHannot\UtilsBundle\PdfCreator\BeforeOutputPdfCallback;use HeimrichHannot\UtilsBundle\PdfCreator\PdfCreatorFactory;
use HeimrichHannot\UtilsBundle\PdfCreator\Concrete\MpdfCreator;

$pdf = PdfCreatorFactory::createInstance(MpdfCreator::getType());

$pdf->setBeforeCreateInstanceCallback(function (BeforeCreateLibraryInstanceCallback $callbackData) {
    $parameter = $callbackData->getConstructorParameters();
    $parameter['config']['fonttrans'] = [
        'rotis-sans-serif-w01-bold' => 'rotis-sans-serif',
        'rotissansserifw01-bold' => 'rotis-sans-serif',
    ];
    $callbackData->setConstructorParameters($parameter);
    return $callbackData;
});

$pdf->setBeforeOutputPdfCallback(function (BeforeOutputPdfCallback $callbackData) use ($pdf) {
    $mpdf = $callbackData->getLibraryInstance();
    $mpdf->AddPage();
    $parameters = $callbackData->getOutputParameters();
    $parameters['name'] = 'custom_'.$pdf->getFilename();
    $callbackData->setOutputParameters($parameters);
});

```

## Documentation

- [API Documentation](https://heimrichhannot.github.io/pdf-creator/)