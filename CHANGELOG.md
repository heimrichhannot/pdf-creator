# Changelog
All notable changes to this project will be documented in this file.   
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.1] - 2022-07-27
- Added: DompdfCreator now supports output mode file
- Fixed: DompdfCreator mastertemplate invalid return value
- Fixed: MpfCreator not correctly registering fonts

## [0.4.0] - 2022-07-26
- Changed: [API Change] AbstractPdfCreator::render() now returns PdfCreatorResult
- Changed: allow php 8
- Fixed: output mode file for tcpdf and mpdf

## [0.3.3] - 2021-05-25
- added temp file cleanup to dompdf master template support
- added missing symfony/filesystem dependency

## [0.3.2] - 2021-05-25
- added methods to get and set temp path to AbstractPdfCreator
- added master template support to dompdf (through fpdi with tcpdf)

## [0.3.1] - 2021-04-30
- fixed dompdf render method callback before render callback
- fixed variable naming in DompdfCreator

## [0.3.0] - 2021-04-20
- added Dompdf support
- added `AbstractPdfCreator::supports()` and `AbstractPdfCreator::isSupported()` method to check if a pdf creator supports a specific feature
- [BC BREAK] removed void return type from abstract `AbstractPdfCreator::render()` method to support returning a pdf as string
- removed return type from `AbstractPdfCreator::getFormat()` as it can also return an array
- enhanced code documentation
- fixed return values for string output for mpdf and tcpdf creator classes

## [0.2.0] - 2021-02-26
- added abstract isUsable methode to AbstractPdfCreator
- added MissingDependenciesException
- made type property protected in PdfCreatorFactory

## [0.1.0] - 2021-02-26

Initial release

Changes to the utils bundle version:
- added tcpdf support
- added pdf creator type to the callbacks
- bugfixes
