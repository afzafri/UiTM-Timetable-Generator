const { jsPDF } = window.jspdf;

// js native equivalent of jQuery $(document).ready(function {..});
document.addEventListener("DOMContentLoaded", function (event) {

    try {
        doRequest("api.php?getlist", null, true, function (data) {

            var list = JSON.parse(data);
            var elem = document.querySelector('#listcampus');

            for (var i = 0; i < list.length; i++) {
								// Seperate campus & faculty
								// ARSHAD AYUB GRADUATE BUSINESS SCHOOL
								if (list[i].code === 'AA') {
										elem = document.querySelector('#listfaculty');
								}

                var el = document.createElement('option');
                el.value = list[i].code;
                el.innerHTML = list[i].fullname;
                elem.appendChild(el);
            }

						initSelect('select-campus');
						initSelect('select-faculty');
        });

        vex.defaultOptions.className = 'vex-theme-os';

    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
});

// loading box status
//
// true = enable
// false = disable
var lboxStatus = true;

var listsubject;
var group = {};

// used by automatic data fetcher
var fetched_data = null;
var automatic_fetch = false;
var group_prev = {};
var index_list = 0;

// change if user choose any campus from select list
document.querySelector('#listcampus').onchange = function () {

		// If campus B (Selangor) selected
		let code = document.querySelector('#listcampus').value;
		if (code[0] === 'B') {
				document.querySelector('#listfaculty').value = '';
				document.querySelector('#div-faculty').hidden = false;
				
				clearTable();

				return;
		} else {// If non-campus B (Selangor) selected
				document.querySelector('#listfaculty').value = '';
				document.querySelector('#div-faculty').hidden = true;
		}

		showNewTable();
}

document.querySelector('#listfaculty').onchange = function () {
		showNewTable();
}

var showNewTable = function() {

    try {

				clearTable();

        // create first row table
        addNewRow();

				var campus = document.querySelector('#listcampus').value;
				var faculty = document.querySelector('#listfaculty').value;

        doRequest('api.php?getsubject', 'campus=' + campus + '&faculty=' + faculty, true, function (data) {

            if (data != '') {

                listsubject = JSON.parse(data);

                var elem = document.querySelector('.row-select:last-child .select-subject');

                elem.innerHTML = '<option value="">Select Subject</option>';

                for (var i = 0; i < listsubject.length; i++) {

                    var el = document.createElement('option');
                    el.value = listsubject[i].subject;
                    el.innerHTML = listsubject[i].subject;

                    elem.appendChild(el);
                }

                initSelect('select-subject');

                // add new row
                addNewRow();

                // take over automatic fetcher from .login
                if (automatic_fetch == true) {

                    // remove if any subject non-exist in listsubject
                    if (lboxStatus == false) {
                        for (k in fetched_data) {
                            if (listsubject.indexOf(k) < 0) {
                                delete fetched_data[k];
                            }
                        }
                    }

                    // jump to processing function
                    processCourses();
                }

            }
        });

    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
};

var clearTable = function() {
		var tree_elem = document.querySelectorAll('.newtable tr');

		// remove existing row if user changes campus
		for (var i = 1; i < tree_elem.length; i++) {
				tree_elem[i].parentNode.removeChild(tree_elem[i]);
		}

		// remove previous table before drawing a new one
		document.querySelector('.timetable').innerHTML = '';

		// change property of select-table depend on user selected choice
		document.querySelector('#select-table').style.display = this.value != '' ? 'block' : 'none';

		// reset colors input and hide the tools section before render new table
		resetTableSubject();
		changeColours('default');
		listSubjectsColour();
		document.getElementById('tools').style.display = 'none';
}

var processCourses = function () {

    try {

        if (Object.keys(fetched_data).length > 0) {

            var index = null;

            // (bad) trick to get first key property from object
            for (var k in fetched_data) {
                index = k;
                break;
            }

            // add into dictionary for later
            group_prev[index_list] = fetched_data[index];

            // delete each one element until "fetched_data" is empty
            delete fetched_data[index];

            var select_subject = document.querySelectorAll('.select-subject')[index_list++];

            select_subject.value = index; // key = subject

            // because .select-subject was created dynamically
            // then we need to bubble it up
            select_subject.dispatchEvent(new CustomEvent('change', {bubbles: true}));

            // recursively do this again
            processCourses();

        } else {

            // all done!
            // now one last thing
            // select the group based on student's courses

            index_list = 0; // reset to initial index

            for (k in group_prev) {

                var select_group = document.querySelectorAll('.select-group')[index_list];
                select_group.value = group_prev[k];

                // because .select-group was created dynamically
                // then we need to bubble it up
                select_group.dispatchEvent(new CustomEvent('change', {bubbles: true}));

                index_list++; // go to its next element
            }

            // reset all data
            fetched_data = null;
            automatic_fetch = false;
            group_prev = {};
            index_list = 0;

            // hide loading box
            blockLoadingBox(false);
        }

    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
};

/*
 * using event delegation to set an event to the dynamic created elements
 * guide : https://davidwalsh.name/event-delegate
 * other reference : http://javascript.info/tutorial/bubbling-and-capturing
 */

document.querySelector('.newtable').onmousedown = function (e) {

    try {

        if (e.target && e.target.matches(".row-select:last-child .select-subject")) {

            var el = document.createElement('option');
            el.innerHTML = '<option value="">Select subject</option>';
            e.target.appendChild(el);

            for (var i = 0; i < listsubject.length; i++) {
                el = document.createElement('option');
                el.value = listsubject[i].subject;
                el.innerHTML = listsubject[i].subject;
                e.target.appendChild(el);
            }

            // init select plugin and toggle
            initSelect('select-subject');
            toggleSelect();

            // add new row into last position
            addNewRow();
        }
    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }

};

document.querySelector('.newtable').onchange = function (e) {

    try {

        // delegate event for select-subject
        if (e.target && e.target.matches(".select-subject")) {

            var campus = document.querySelector('#listcampus').value;
            var faculty = document.querySelector('#listfaculty').value;
            var subject = e.target.value;

            if (subject != '') {

                var exec = function () {

                    var parent = parents(e.target, '.row-select');
                    var elem = parent.querySelector('.select-group');

                    // clear previous data in select-group selectform
                    elem.innerHTML = '<option value="">Select group</option>';

                    for (k in group[subject]) {
                        var el = document.createElement('option');
                        el.value = k;
                        el.innerHTML = k;
                        elem.appendChild(el);
                    }

                    if (automatic_fetch == true && Object.keys(fetched_data).length > 0) {

                        // create mousedown event on .select-subject based on new index_list value
                        // this is to ensure that javascript loads all the subjects before automatic system do it jobs
                        document.querySelectorAll('.select-subject')[index_list].dispatchEvent(new CustomEvent('mousedown', {bubbles: true}));
                    }

                };

                // fetch data if it does not exist in Object data yet
                if (!group[subject]) {
                    doRequest('api.php?getgroup', 'subject=' + subject + '&faculty=' + faculty + '&campus=' + campus, false, function (data) {
                        if (data != '') {
                            group[subject] = JSON.parse(data);
                            exec();
                        }
                    });
                }

                exec();
            }

        // delegate event for select-group
        } else if (e.target && e.target.matches(".select-group")) {

            var groups = document.querySelectorAll('.select-group');
            var datagroup = [];
            var canuse = [];

            // filter any select who is currently selecting empty option
            for (var i = 0; i < groups.length; i++) {
                if (groups[i].selectedIndex >= 0 && groups[i].value != '') {
                    var ssubj = parents(groups[i], '.row-select').querySelector('.select-subject');
                    datagroup[ssubj.value] = group[ssubj.value][groups[i].value];
                    canuse.push(groups[i]);
                }
            }

            var clashCheck = isClash(canuse);

            // check if group times are clashing
            if (clashCheck != false)
                alertify.error("Timetable clash (" + clashCheck[0] + " - " + clashCheck[1] + ") ! Please choose another groups.");
            else
                alertify.success("Successfully rendered!");

            var places = [];
            var info = [];
            var exportData = [];
            var c = 0;
            var minTime = 23.59, maxTime = 0.0;

            var getSubject = parents(e.target, '.row-select').querySelector('.select-subject').value;

            for (var k in datagroup) {

                // ignore drawing clashing data
                if (clashCheck && Object.keys(datagroup).length > 1 && k.indexOf(getSubject) >= 0)
                    continue;

                for (var j = 0; j < datagroup[k].length; j++) {

                    places.push(datagroup[k][j][6]);

										let [day, startTime, endTime] = parseDayTime(datagroup[k][j][0]);
                    startTime = convertDate(startTime);
                    endTime = convertDate(endTime);

                    minTime = Math.min(startTime, minTime);
                    maxTime = Math.max(endTime, maxTime);

                    var start = startTime.toString().split('.');
                    var end = endTime.toString().split('.');

                    var endFirst = !start[1] ? 0 : parseFloat(start[1]);
                    var endSecon = !end[1] ? 0 : parseFloat(end[1]);

                    var classroom = datagroup[k][j][4];
                    var classStart = startTime;
                    var classEnd = endTime;
                    var dayName = day;
                    var classGroup = groups[c].value;

                    var name = '<h5>' + k + '</h5>' +
                        '<p><i>' + classroom + '</i></p>' +
                        '<p>' + classStart + '-' + classEnd + '</p>';

                    info.push({
                        name: name,
                        loc: dayName,
                        startH: parseFloat(start[0]),
                        startM: endFirst,
                        endH: parseFloat(end[0]),
                        endM: endSecon
                    });

                    // Array data for export feature
                    exportData.push({
                        day: dayName,
                        subject: k,
                        group: classGroup,
                        classroom: classroom,
                        class_start: classStart,
                        class_end: classEnd
                    });
                }
                c++;
            }

            // convert array to JSON, append to textarea for fetching later
            document.getElementById('exportData').value = JSON.stringify(exportData);

            var timetable = new Timetable();
            timetable.setScope(Math.floor(minTime), Math.ceil(maxTime));
            timetable.addLocations(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']);

            // add event
            for (var i = 0; i < Object.keys(info).length; i++) {
                timetable.addEvent(info[i].name, info[i].loc,
                        new Date(0, 0, 0, info[i].startH, info[i].startM),
                        new Date(0, 0, 0, info[i].endH, info[i].endM), '#');
            }

            var renderer = new Timetable.Renderer(timetable);

            // remove previous table before drawing new one
            document.querySelector('.timetable').innerHTML = '';

            renderer.draw('.timetable'); // any css selector

            // reset colors input and show the tools section before render new table
            resetTableSubject();
            changeColours('default');
            listSubjectsColour();
            document.getElementById("tools").style.display = 'block';

        }

				initSelect('select-group');
				
    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
};

document.querySelector('.newtable').onclick = function (e) {

    try {

        // delegate events

        if (e.target && e.target.matches(".delete-subject")) {

            var parentNode = e.target.parentNode.parentNode;
            var subjectElem = parentNode.querySelector('.select-subject');
            var groupElem = parentNode.querySelector('.select-group');
            subjectElem[0].selected = true;
            groupElem[0].selected = true;
            subjectElem.dispatchEvent(new CustomEvent('change', {bubbles: true}));
            groupElem.dispatchEvent(new CustomEvent('change', {bubbles: true}));

        } else if (e.target && e.target.matches(".delete-group")) {

            var groupElem = e.target.parentNode.parentNode.querySelector(".select-group");
            groupElem[0].selected = true;
            groupElem.dispatchEvent(new CustomEvent('change', {bubbles: true}));

        }

    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }

}

// Outdated script - not working anymore
// document.querySelector('.login').onclick = function (e) {

//     try {

//         vex.dialog.open({
//             message: 'Enter your UiTM\'s ID no (matrix no.) :',
//             input: [
//             '<input name="id" type="text" placeholder="Student\'s matrix ID" required />' +
//             '(This is alpha feature! Consider manual adjusting if it doesn\'t works)'
//             ].join(''),
//             buttons: [
//             extend({}, vex.dialog.buttons.YES, {text: 'Automatic fetch!'}),
//             ],
//             callback: function (formData) {
//                 if (formData) {

//                     // block loading box
//                     blockLoadingBox(true);

//                     doRequest('api.php?fetchDataMatrix', 'studentId=' + formData.id, true, function (data) {

//                         if (data != '') {
//                             data = JSON.parse(data);
//                             var elemUiTMSelect = document.querySelector('#listfaculty');
//                             automatic_fetch = true;
//                             fetched_data = data['Courses']; // hand it over global variable
//                             elemUiTMSelect.value = data['UiTMCode'];
//                             elemUiTMSelect.dispatchEvent(new CustomEvent('change', {}));
//                         }
//                     });
//                 }
//             }
//         })

//     } catch (e) {
//         alertify.delay(10000).error(e);
//         blockLoadingBox(false);
//     }

// };

function addNewRow() {

    try {
        var elems = document.querySelectorAll('.select-subject');
        var elem = document.createElement('tr');

        elem.className = 'row-select';
        elem.innerHTML = '<td width="50px">' + (elems.length + 1) + '</td>\
                          <td><select class="select-subject"></select></td>\
                          <td><button class="delete-subject button-delete"></button></td>\
                          <td><select class="select-group"></select></td>\
                          <td><button class="delete-group button-delete"></button></td>';

        document.querySelector('.newtable tbody').appendChild(elem);
    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
}

function isClash(canuse) {

    try {

        // check here
        for (var i = 0; i < canuse.length; i++) {
            for (var j = i + 1; j < canuse.length; j++) {

                var ssubjsrc = parents(canuse[i], '.row-select').querySelector('.select-subject');
                var datasrc = group[ssubjsrc.value][canuse[i].value];

                var ssubjdst = parents(canuse[j], '.row-select').querySelector('.select-subject');
                var datadst = group[ssubjdst.value][canuse[j].value];

                for (var z = 0; z < datasrc.length; z++) {
                    for (var x = 0; x < datadst.length; x++) {

                        // if in same day
                        // then check if time is clash
                        let [day1, startTime1, endTime1] = parseDayTime(datasrc[z][0]);
                        let [day2, startTime2, endTime2] = parseDayTime(datadst[x][0]);
                        if (day1 === day2) {
                            // time 1
                            var stimesrc = convertHourToMinutes(startTime1);
                            var etimesrc = convertHourToMinutes(endTime1);

                            // time 2
                            var stimedst = convertHourToMinutes(startTime2);
                            var etimedst = convertHourToMinutes(endTime2);

                            /*  -- Here on how it works --

                                So let us ask a simple question, given two times, how to
                                check if both are clashing against each others?

                                Simple observations :

                                1)

                                    Time 1: 2.00 PM - 4.00 PM
                                    Time 2: 3.45 PM - 5.00 PM

                                        time clashing -----------
                                                                |
                                                                |
                                                                ▼
                                    ----------------------------------
                                    |       2.00 PM - 4.00 PM        |
                                    ----------------------------------
                                                                ------------------------------
                                                                |       3.45 PM - 5.00 PM    |
                                                                ------------------------------

                                2)

                                    Time 1: 2.00 PM - 4.00 PM
                                    Time 2: 4.15 PM - 5.00 PM

                                                time not clashing --------
                                                                         |
                                                                         |
                                                                         ▼
                                    ----------------------------------
                                    |       2.00 PM - 4.00 PM        |
                                    ----------------------------------
                                                                           ------------------------------
                                                                           |       4.15 PM - 5.00 PM    |
                                                                           ------------------------------

                                So from these two simple observations, we can derive a pretty simple algorithm, which
                                sufficiently follows below rules :

                                    1) If time1 <= time2, then times aren't clashing
                                    2) If time1 >= time2, then times aren't clashing

                                So, back to our original question, how to check if times are clashing? Well, taking the counter-example
                                from above two rules, then we can deduce in order the times to clash, both of above rules must be INCORRECT.

                                    if ( incorrect(time1 <= time2) and incorrect(time1 >= time2) ) {
                                        # times are clashing
                                    }

                               */

                            // check if time are clashing
                            var cond_before = stimesrc < stimedst && etimesrc <= stimedst;
                            var cond_after  = stimesrc >= etimedst && etimesrc > etimedst;

                            // if clashing, then return true
                            if (!cond_before && !cond_after) {
                                return [ssubjsrc.value, ssubjdst.value];
                            }
                        }
                    }
                }
            }
        }

        return false;

    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
}

function parseDayTime(dayTime) {
	// "MONDAY ( 12:00 PM-13:00 PM )" <-- dayTime format
	dayTime = dayTime.split('(');

	let day = dayTime[0];
	let time = dayTime[1];

	time = time.replace(')', '');
	day = day.trim();
	time = time.trim();
	let [startTime, endTime] = time.split('-');

	return [day, startTime, endTime];
}

function convertDate(time) {

    try {

        // find am/pm index (using only 'm' character)
        var index = time.indexOf("M");

        // compute real time length (either am or pm)
        var getTime = time.substr(0, index - 1);

        // get hour & minute
        var getHour = parseFloat(getTime.substr(0, getTime.indexOf(':')));
        var getMinutes = getTime.substr(getTime.indexOf(':') + 1, 2);

        // get either pm or am
        var dateIndi = time.substr(index - 1, 2);

        if (dateIndi === 'pm' && getHour < 12) {
            getHour += 12;
        }

        return getHour + '.' + getMinutes;

    } catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
}

function convertHourToMinutes(time) {
    time = convertDate(time).split(".");
    return parseInt(time[0])*60 + parseInt(time[1]);
}

/*
 * url        : which url we want to do a HTTP request
 * postdata   : data to send to server if only you want to do 'POST' type request
 *              if you are only want GET request, then abandon this parameter
 *              (if you don't want to send POST data, then set it to null)
 * async      : set either if you want asycn (true) or sync (false)
 * func(data) : event function that accept string from server responds
 *
 * if you want to use func event with GET request, then set `data` parameter to null, example is
 * --> doRequest('abc.php', null, true, function (data) {...});
 *
 * if you want to do both func event and POST request, then set `data` with POST data you want to send
 * --> doRequest('abc.php', 'password=jengjengjeng', true, function (data) {...});
 *
 * note that this is self home-made function, so least error checking is made into this code
 */

function doRequest(url, postdata, async, func) {

    try {

        var http = new XMLHttpRequest();
        http.open("POST", url, async);
        http.onloadstart = function (e) {
            blockLoadingBox(true);
        };

        http.onreadystatechange = function () {
            blockLoadingBox(false);
            if (this.readyState === 4) {
                if (this.status >= 200 && this.status < 400) {
                    if (this.responseText == '') {
                        alertify.delay(20000).error("API returns nothing.\nMaybe an error have happened.\n Try again later...");
                    } else if (this.responseText == '[]') {
                        alertify.delay(20000).error("Request returns no data!\nNo internet connection or server problem?");
                    } else if (this.responseText.includes("Alert_Error")) {
                        var errormsg = this.responseText.split(':')[1].trim();
                        alertify.delay(20000).error(errormsg);
                    } else {
                        alertify.delay(5000).success("Fetching data succeed!");
                        func(this.responseText);
                    }
                } else {
                    alertify.delay(20000).error("There is an error when doing an Ajax request!\nHTTP Error Code :" + this.status);
                }
            }
        };

        http.ontimeout = function () {
            alertify.delay(20000).error('Error request! No internet or server problem?');
            blockLoadingBox(false);
        };

        if (postdata != '' && postdata != null) {
            // check if the data is FormData, then it is file upload. no need to set content type
            if(!(postdata instanceof FormData)) {
              // send the proper header information along with the request
              http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            }
            // send POST request with out data
            http.send(postdata);
        } else {
            http.send();
        }

    } catch (e) {
        alertify.delay(20000).error(e);
        blockLoadingBox(false);
    }
}

var blockLoadingBox = function (bool) {
    var loadingBox = document.querySelector('.se-pre-con');
    loadingBox.style.display = bool == true ? 'block' : 'none';
    lboxStatus = bool;
};

function parents(nodeCur, parentMatch) {
    for (; !nodeCur.matches(parentMatch); nodeCur = nodeCur.parentNode) {}
    return nodeCur;
}

/*
 * un-shamefully stole from youmightnotneedjquery.com
 * ;)
 */
function extend(out) {
    out = out || {};

    for (var i = 1; i < arguments.length; i++) {
        if (!arguments[i]) continue;
        for (var key in arguments[i]) {
            if (arguments[i].hasOwnProperty(key))
                out[key] = arguments[i][key];
        }
    }

    return out;
};

// Save (download) timetable as image
function saveImg() {

    try {
        // set viewport meta width, so even on mobile, page will rendered desktop mode. needed for full screenshot
        var element = document.getElementsByName("viewport")[0];
        element.setAttribute("content", "");
        document.body.style.zoom="60%"; // zoom out the page, for low res screen

        // use html2canvas js library, to convert the content into html5 "canvas"
        var timearea = document.getElementById("timetable");
        html2canvas(timearea, { scale: 3 }).then((canvas) => {
          // create new hyperlink with download attribute, set the image url, auto click the link to download
          var link = document.createElement('a');
          link.download = 'timetable.png';
          link.href = canvas
            .toDataURL('image/png')
            .replace('image/png', 'image/octet-stream');
          link.click();

          // restore back the responsive viewport meta and zoom leve
          element.setAttribute(
            'content',
            'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no'
          );
          document.body.style.zoom = '100%';
        });
    }
    catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }

}

// Save (download) timetable as PDF file
function savePDF() {
    try {
        // Get the <div> element to convert
        const divToConvert = document.getElementById('timetable');
        
        // Create a new jsPDF instance
        const doc = new jsPDF({
            orientation: "landscape",
            // unit: "in",
            // format: [4, 2]
        });

        // Convert the <div> element to PDF using html2canvas library
        html2canvas(divToConvert).then((canvas) => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = doc.internal.pageSize.getWidth();
            const imgHeight = canvas.height * imgWidth / canvas.width;

            // Add the image to the PDF document
            doc.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);

            const currentDate = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).replace(/\//g, '-');

            // Download the PDF file
            doc.save(`UiTM-Timetable-Generator-${currentDate}.pdf`);
        });
    }
    catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }
}
// Save (download) timetable as Excel spreadsheet
function saveExcel() {

    try {
        var timetableInfo = document.getElementById("exportData").value;
        timetableInfo = JSON.parse(timetableInfo);

        const workbook = new ExcelJS.Workbook();
        workbook.creator = 'UiTM Timetable';
        workbook.lastModifiedBy = 'UiTM Timetable';
        workbook.created = new Date();
        workbook.modified = new Date();
        workbook.lastPrinted = new Date();

        const worksheet = workbook.addWorksheet('timetable');

        worksheet.columns = [
            { header: 'DAY', key: 'day' },
            { header: 'SUBJECT', key: 'subject' },
            { header: 'GROUP', key: 'group' },
            { header: 'PLACE', key: 'classroom' },
            { header: 'START TIME', key: 'class_start' },
            { header: 'END TIME', key: 'class_end' },
        ];

        for (var i = 0; i < timetableInfo.length; i++) {
            var rowData = timetableInfo[i];
            worksheet.addRow({id: (i+1), day: ucwords(rowData.day), subject: rowData.subject, group: rowData.group, classroom: rowData.classroom, class_start: rowData.class_start, class_end: rowData.class_end});
        }

        // Set up the response headers to trigger a download of the Excel file
        const fileName = 'UiTM-Timetable-' + Date.now() + '.xlsx';
        const fileType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        workbook.xlsx.writeBuffer().then(function(buffer) {
            const blob = new Blob([buffer], {type: fileType});
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
        });
    }
    catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }

}

// Import (upload) timetable .xlsx Excel file and render timetable
function importExcel() {

    try {
        var excelFile = document.getElementById("excelFile").files;
        var formData = new FormData();

        if (!excelFile[0].type.match('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
          alertify.delay(10000).error('Invalid file! Only xlsx file are allowed.');
        } else {

            // Get the uploaded file from the event
            const file = excelFile[0];

            // Create a new file reader
            const reader = new FileReader();

            // Set up the file reader to read the uploaded file as a binary string
            reader.readAsBinaryString(file);

            // Once the file has been read, convert it to a JSON object
            reader.onload = function() {
                // Create a new workbook object from the binary string
                const workbook = new ExcelJS.Workbook();
                workbook.xlsx.load(reader.result).then(function() {
                    // Get the first worksheet from the workbook
                    const worksheet = workbook.worksheets[0];

                    // Get the data rows
                    const dataRows = [];
                    worksheet.eachRow({ includeEmpty: false, skipHeader: true }, function(row, rowNumber) {
                        // Skip the first row (i.e., the header row)
                        if (rowNumber > 1) {
                            // Create a new object to store the row data
                            const rowData = {};
                            row.eachCell({ includeEmpty: false }, function(cell, colNumber) {
                                // Use the column names to set the property names in the object
                                rowData[colNumber - 1] = cell.value;
                            });
                            // Push the row data object into the array
                            dataRows.push(rowData);
                        }
                    });

                    // generate Timetable
                    var timetable = dataRows;
                    var message = "Timetable Imported";
                    var info = [];
                    var exportData = [];
                    var minTime = 23.59, maxTime = 0.0;

                    for(var i=0;i<timetable.length;i++) {
                        var startTime = timetable[i][4]
                        var endTime = timetable[i][5];

                        minTime = Math.min(startTime, minTime);
                        maxTime = Math.max(endTime, maxTime);

                        var start = startTime.toString().split('.');
                        var end = endTime.toString().split('.');

                        var endFirst = !start[1] ? 0 : parseFloat(start[1]);
                        var endSecon = !end[1] ? 0 : parseFloat(end[1]);

                        var subject = timetable[i][1];
                        var classroom = timetable[i][3];
                        var classStart = timetable[i][4];
                        var classEnd = timetable[i][5];
                        var dayName = ucwords(timetable[i][0]);
                        var classGroup = timetable[i][2];
                        var name = '<h5>' + subject + '</h5>' +
                                    '<p><i>' + classroom + '</i></p>' +
                                    '<p>' + classStart + '-' + classEnd + '</p>';

                        info.push({
                            name: name,
                            loc: dayName,
                            startH: parseFloat(start[0]),
                            startM: endFirst,
                            endH: parseFloat(end[0]),
                            endM: endSecon
                        });

                        // Array data for export feature
                        exportData.push({
                            day: dayName,
                            subject: subject,
                            group: classGroup,
                            classroom: classroom,
                            class_start: classStart,
                            class_end: classEnd
                        });

                    }

                    // convert array to JSON, append to textarea for fetching later
                    document.getElementById('exportData').value = JSON.stringify(exportData);

                    var timetable = new Timetable();
                    timetable.setScope(Math.floor(minTime), Math.ceil(maxTime));
                    timetable.addLocations(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);

                    // add event
                    for (var i = 0; i < Object.keys(info).length; i++) {
                        timetable.addEvent(info[i].name, info[i].loc,
                                new Date(0, 0, 0, info[i].startH, info[i].startM),
                                new Date(0, 0, 0, info[i].endH, info[i].endM), '#');
                    }

                    var renderer = new Timetable.Renderer(timetable);
                    // remove previous table before drawing new one
                    document.querySelector('.timetable').innerHTML = '';

                    renderer.draw('.timetable'); // any css selector
                    // reset colors input and show the tools section before render new table
                    resetTableSubject();
                    changeColours('default');
                    listSubjectsColour();
                    document.getElementById("tools").style.display = 'block';

                    alertify.success(message);

                });
            };
        }
    }
    catch (e) {
        alertify.delay(10000).error(e);
        blockLoadingBox(false);
    }

    // clear input
    document.getElementById("excelFile").value = "";
}

// Change timetable events colours scheme (background, border, text)
function changeColours(type)
{
    // fetch list of events nodes
    var events = document.getElementsByClassName('time-entry');

    // fetch choosen colors
    // check if type is set to 'default', set var value to default colors
    var bg_color = (type != 'default') ? document.getElementById('change_bg_color').value : '#EC6A5E';
    var border_color = (type != 'default') ? document.getElementById('change_border_color').value : '#e32c1b';
    var text_color = (type != 'default') ? document.getElementById('change_text_color').value : '#ffffff';

    // iterate through the nodes, change the colors
    for(var i=0; i<events.length; i++)
    {
        events[i].style.backgroundColor = bg_color;
        events[i].style.borderColor = border_color;
        events[i].style.color = text_color;
    }

    // if type is set to 'default' reset back the colors pickers to default colors
    if(type == 'default')
    {
        document.getElementById('change_bg_color').value = '#EC6A5E';
        document.getElementById('change_border_color').value = '#e32c1b';
        document.getElementById('change_text_color').value = '#ffffff';
    }
}

// Change event colours scheme for each subject
function changeSubjectColours(subject)
{
    // fetch list of events nodes
    var events = document.getElementsByClassName('time-entry');

    // fetch choosen colors
    var bg_color = document.getElementById('change_bg_color'+subject).value;
    var border_color = document.getElementById('change_border_color'+subject).value;
    var text_color = document.getElementById('change_text_color'+subject).value;

    // iterate through the nodes, change the colors
    for(var i=0; i<events.length; i++)
    {
        var name = events[i].getElementsByTagName('h5')[0].innerHTML;

        if(name == subject)
        {
            events[i].style.backgroundColor = bg_color;
            events[i].style.borderColor = border_color;
            events[i].style.color = text_color;
        }
    }
}

// function to fetch and list down the subjects for changing colors
function listSubjectsColour()
{
    // fetch list of events nodes
    var events = document.getElementsByClassName('time-entry');
    var subjectsname = [];

    for(var i=0; i<events.length; i++)
    {
        var name = events[i].getElementsByTagName('h5')[0].innerHTML;
        if(subjectsname.indexOf(name) == -1)
        {
            subjectsname.push(name);
        }
    }

    // append list of subject with color input
    var table = document.getElementById('subjectColorTable');
    for(var i=0; i<subjectsname.length; i++)
    {
        var row = table.insertRow(i+2);

        var cell1 = row.insertCell(0);
        var cell2 = row.insertCell(1);
        var cell3 = row.insertCell(2);
        var cell4 = row.insertCell(3);

        var funcname = "changeSubjectColours('"+subjectsname[i]+"')";

        cell1.innerHTML = subjectsname[i];
        cell2.innerHTML = '<input id="change_bg_color'+subjectsname[i]+'" type="color" onchange="return '+funcname+'">';
        cell3.innerHTML = '<input id="change_border_color'+subjectsname[i]+'" type="color" onchange="return '+funcname+'">';
        cell4.innerHTML = '<input id="change_text_color'+subjectsname[i]+'" type="color" onchange="return '+funcname+'">';
    }

}

// reset table, remove current list of subjects
function resetTableSubject()
{
    var table = document.getElementById('subjectColorTable');
    table.getElementsByTagName('tbody')[0].innerHTML = "<tr>" + table.rows[0].innerHTML + "</tr><tr>" + table.rows[1].innerHTML + "</tr>";
}

// Function to initialize blob-select plugin
function initSelect(className)
{
  var selects = document.getElementsByClassName(className);
  for(var i=0;i<selects.length;i++)
  {
    // init plugin
    selects[i].blobSelect.init({
      search: 'true',
    });

    // destroy init, to allow the select box dynamically fill in with data
    selects[i].blobSelect.destroy();

    // re-init the plugin. This way is not clean, but serve as temporary workaround
    selects[i].blobSelect.init({
      search: 'true',
    });
  }
}

// Since the select box is dynamically rendered and populated
// after click event, this causes the plugin to initialized, but not toggling the select box.
// so we need to re-toggle the select box again
function toggleSelect()
{
  var selects = document.getElementsByClassName('blobselect');
  selects[selects.length-1].click();
}

// Capitalize first character for every words
function ucwords(str) {
  return str.toLowerCase().replace(/\b[a-z]/g, function(char) {
    return char.toUpperCase();
  });
}
