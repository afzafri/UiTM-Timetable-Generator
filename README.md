
# UiTM Timetable Generator
Fetch and generate Timetable from iCress UiTM

Official website for this project: https://uitmtimetable.com (thanks [Adib](https://github.com/adibzter))!  
Alternative Android mobile app: https://play.google.com/store/apps/details?id=com.ajeeq.uitmscheduler (thanks [Haziq](https://github.com/ajeeq))

## Created By :
1. Afif Zafri, Mohd Shahril & Syed Muhamad Danial
2. Created Date : 24/1/16
3. Contact Us :
  - Original Devs (No longer active in maintaining this project):
    - http://fb.me/afzafri
    - http://fb.me/mohdshahril.net
    - https://www.facebook.com/syedmdanial.sd
  - Current Maintainer:
    - https://twitter.com/adibzter

## Changelogs
1. UPDATE 1 : 10/4/16 - Initial design
2. UPDATE 2 : 13/4/16 - Major code overhaul, updated by Shahril
3. UPDATE 3 : 20/9/16 - Another major code overhaul, added automatic timetable creator
4. UPDATE 4 : 20/9/16 - Major design changes by Syed Danial
5. UPDATE 5 : 1/12/16 - Little update, fixed floating footer issue, and added mobile view support
6. UPDATE 6 : 22/12/16 - Added feature for saving the timetable to PNG image file (experimental)
7. UPDATE 7 : 17/3/17 - Added feature for customizing the events colours scheme (background, border, text)
8. UPDATE 8 : 3/9/18 - Added feature to search for subject code inside the select subject box
9. UPDATE 9 : 26/2/19 - Added feature to export timetable to Excel spreadsheet file, and also import back the Excel spreadsheet file into the system to render timetable
10. UPDATE 10: 30/9/22 - Fixed day & time parsing, added example of icress table for future debugging in references folder
11. UPDATE 11: 10/10/22 - Fixed missing Subjects & Groups. Reason: iCress upgraded, and most of the URL changed.
12. UPDATE 12: 23/3/23 - Added feature to export timetable to PDF file (thanks [Naim](https://github.com/naimhasim))
13. UPATE 13: 18/4/23 - Fixed export to Excel issue, move write and read Excel file process to client side.


## Credit
1. Mohd Shahril - Regex code for fetching and sort data (1st version), Major code overhaul and improvement
2. Syed Muhammad Danial - Great UI improvement.
3. Timetable.js
   - Webpage : http://timetablejs.org/
   - GitHub : https://github.com/Grible/timetable.js
4. html2canvas
   - Webpage : https://html2canvas.hertzen.com/
   - GitHub : https://github.com/niklasvh/html2canvas/
5. blob-select
   - GitHub : https://github.com/Blobfolio/blob-select
6. PhpSpreadsheet
   - Webpage : https://phpspreadsheet.readthedocs.io
   - GitHub : https://github.com/PHPOffice/PhpSpreadsheet
7. jsPDF
   - Webpage : https://parall.ax/products/jspdf
   - GitHub : https://github.com/parallax/jsPDF
8. ExcelJS
   - GitHub : https://github.com/exceljs/exceljs
9. Muhammad Nabil - For sponsoring domain and hosting, thanks! (uitmtimetable.com)
10. Adib Zaini - Current maintainer, sponsored new domain and hosting (uitmtimetable.com)

## Installation

1) Drag and drop all files into your web server directory. For eq; Apache2 for Ubuntu is located in `/var/www/html`.
2) Don't forget to set the correct permission as this timetable writes cache file.
  ```
  sudo chown -R apache:apache /path/to/UiTM-Timetable-Generator/
  sudo find /path/to/UiTM-Timetable-Generator/ -type f -exec chmod 644 {} \;
  sudo find /path/to/UiTM-Timetable-Generator/ -type d -exec chmod 755 {} \;
  ```
3) Install required PHP additional extensions, run `sudo apt install php-curl php-mbstring php-zip php-xml` and you'll be fine.
4) Adjust `config.php` to suite your need. and Voila!

## Usage

1. Select faculty or campus.
2. Select courses that you want the timetable to generate.
3. (Optional) Change colour for each subjects.
4. (Optional) Export the timetable image to your device.

## Contributing

1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D

## License

This project is under ```MIT license```, please look at the LICENSE file
