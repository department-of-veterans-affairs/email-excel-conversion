Email Conversion - .xlsx to CSV,JSON,XML
======================

This is a working project to provide a quick and dirty way to convert excel spreadsheets (.xlsx) to other machine readable formats like CSV, JSON and XML.

The goal is to be able to email a spreadsheet to a specific address and get back the same data in all three formats. While this isn't a perfect solution, I hope to stabilize it and offer it as an open source project that can be installed anywhere.

## Dependencies
* PHP 5.x
* [PHP Excel](https://github.com/PHPOffice/PHPExcel)
* Gmail IMAP

## Known Issues
* Ony processes .xlsx and NOT .xls files, we need it to do both
* High memory usage. PHP excel is pretty processsor intensive and you need a high memory EC2 instance (or other server) to make work properly

This current example uses IMAP to check email because I'm using Gmail as the email provider. Next I will add a basic POP option to be used with any other email provider.

The roadmap also includes adding a web page form for uploading CSV and getting CSV, JSON and XML files back through an interface.

You can follow this project here on Github, where you can submit issues. If you would like to be able to use this service as it is, please email me at kinlane@gmail.com or ping me directly at @kinlane.
  
