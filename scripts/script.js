// js native equivalent of jQuery $(document).ready(function {..});
document.addEventListener("DOMContentLoaded", function (event) {

    doRequest("api.php?getlist", null, true, function (data) {

        var list = JSON.parse(data);
        var elem = document.querySelector('#listfaculty');

        for (var i = 0; i < list.length; i++) {

            var el = document.createElement('option');
            el.value = list[i].code;
            el.innerHTML = list[i].fullname;

            elem.appendChild(el);
        }
    });

});

var listsubject;
var group = {};

// change if user choose any faculty/university from select list
document.querySelector('#listfaculty').onchange = function () {

    var trelem = document.querySelectorAll('.newtable tr');

    // remove existing row if user changed faculty/university
    for (var i = 1; i < trelem.length; i++) {
        trelem[i].parentNode.removeChild(trelem[i]);
    }

    // create first row table
    addNewRow();

    doRequest('api.php?getsubject', 'faculty=' + this.value, true, function (data) {

        if (data != '') {

            listsubject = JSON.parse(data);

            var elem = document.querySelector('.row-select:last-child .select-subject');

            elem.innerHTML = '<option value="">Select subject</option>';

            for (var i = 0; i < listsubject.length; i++) {

                var el = document.createElement('option');
                el.value = listsubject[i];
                el.innerHTML = listsubject[i];
                el.id = '';

                elem.appendChild(el);
            }

            // add new row
            addNewRow();

        }
    });

    // change property of select-table depend on user selected choice
    document.querySelector('#select-table').style.display = this.value != '' ? 'block' : 'none';
};

/*
 * using event delegation to set event to dynamic created element
 * guide : https://davidwalsh.name/event-delegate
 * other reference : http://javascript.info/tutorial/bubbling-and-capturing
 */

document.querySelector('.newtable').onmousedown = function (e) {

    if (e.target && e.target.matches(".row-select:last-child .select-subject")) {

        for (var i = 0; i < listsubject.length; i++) {

            var el = document.createElement('option');
            el.value = listsubject[i];
            el.innerHTML = listsubject[i];

            e.target.appendChild(el);
        }

        // add new row into last position
        addNewRow();
    }
};

document.querySelector('.newtable').onchange = function (e) {

    // delegate event for select-subject
    if (e.target && e.target.matches(".select-subject")) {

        var faculty = document.querySelector('#listfaculty').value;
        var subject = e.target.value;

        if (subject != '') {

            var exec = function () {

                var parent = parents(e.target, '.row-select');
                var elem = parent.querySelector('.select-group');

                // clear previous data in select-group selectform
                elem.innerHTML = '<option value="">Select group</option>';

                for (k in group[subject]) {

                    var id = 'group-' + subject + '-' + k;

                    var el = document.createElement('option');
                    el.value = k;
                    el.innerHTML = k;
                    el.id = id;

                    elem.appendChild(el);
                }
            };

            // fetch data if it not exist in Object data yet
            // do sync request we need this data before processing
            if (!group[subject]) {

                doRequest('api.php?getgroup', 'subject=' + subject + '&faculty=' + faculty, true, function (data) {
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

        // filter any select whos currently selecting empty option
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].selectedIndex >= 0 && groups[i].value != '') {

                var ssubj = parents(groups[i], '.row-select').querySelector('.select-subject');
                datagroup[groups[i].value + ' - ' + ssubj.value] = group[ssubj.value][groups[i].value];

                canuse.push(groups[i]);
            }
        }

        var clashCheck = isClash(canuse);

        // check if group time is clashing
        if(clashCheck) {
            alertify.error("Timetable clash! Please choose another groups.");
        }

        console.log(datagroup);

        var places = [];
        var info = [];
        var minTime = 23.59, maxTime = 0.0;

        for(var k in datagroup) {

            // ignore drawing clashing data
            if(clashCheck && Object.keys(datagroup).length > 1 && k.indexOf(e.target.value) >= 0) {
                continue;
            }

            for(var j = 0; j < datagroup[k].length; j++) {

                places.push(datagroup[k][j][6]);

                var startTime = convertDate(datagroup[k][j][1]);
                var endTime = convertDate(datagroup[k][j][2]);

                if(startTime < minTime) {
                    minTime = startTime;
                }

                if(endTime > maxTime) {
                    maxTime = endTime;
                }

                var start = startTime.toString().split('.');
                var end = endTime.toString().split('.');

                var endFirst = !start[1] ? 0 : parseFloat(start[1] + (start[1].length == 1 ? '0' : ''));
                var endSecon = !end[1] ? 0 : parseFloat(end[1] + (end[1].length == 1 ? '0' : ''));

                info.push({
                    name  : k,
                    loc   : datagroup[k][j][3],
                    startH: start[0],
                    startM: endFirst,
                    endH  : end[0],
                    endM  : endSecon
                });
            }
        }

        var timetable = new Timetable();
        timetable.setScope(Math.floor(minTime), Math.ceil(maxTime));
        timetable.addLocations(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);

        // add event
        for(var i = 0; i < Object.keys(info).length; i++) {
            timetable.addEvent(info[i].name, info[i].loc,
                               new Date(0,0,0,info[i].startH,info[i].startM),
                               new Date(0,0,0,info[i].endH,info[i].endM), '#');
        }

        var renderer = new Timetable.Renderer(timetable);

        // remove previous table before draw new one
        document.querySelector('.timetable').innerHTML = '';

        renderer.draw('.timetable'); // any css selector

    }
};

function addNewRow() {

    var elems = document.querySelectorAll('.select-subject');

    var elem = document.createElement('tr');
    elem.className = 'row-select';

    // sorry huduh gila kot :(((

    elem.innerHTML = '\
    <td width="50px">' + (elems.length + 1) + '</td>\
    <td><select class="select-subject"></select></td>\
    <td><select class="select-group"></select></td>';

    document.querySelector('.newtable tbody').appendChild(elem);
}

function isClash(canuse) {

    // check here
    for (var i = 0; i < canuse.length; i++) {
        for (var j = i + 1; j < canuse.length; j++) {

            var ssubjsrc = parents(canuse[i], '.row-select').querySelector('.select-subject');
            var datasrc = group[ssubjsrc.value][canuse[i].value];

            var ssubjdst = parents(canuse[j], '.row-select').querySelector('.select-subject');
            var datadst = group[ssubjdst.value][canuse[j].value];

            /*
             Object
             1 : "11:00am"
             2 : "11:50am"
             3 : "Monday"
             4 : "Full Time"
             5 : "First Timer and Repeater"
             6 : "C303"
             */

            for (var z = 0; z < datasrc.length; z++) {
                for (var x = 0; x < datadst.length; x++) {

                    // if in same day
                    // then check if time is clash
                    if (datasrc[z][3] === datadst[x][3]) {

                        var stimesrc = convertDate(datasrc[z][1]);
                        var etimesrc = convertDate(datasrc[z][2]);

                        var stimedst = convertDate(datadst[x][1]);
                        var etimedst = convertDate(datadst[x][2]);

                        /* here is what happening

                           how can we check if time is clashing?

                           algo that I used is, first we check if (src starttime & src endtime) is lower than dst startime
                           second condition is, we check if (src starttime & src endtime) is higher than dst endtime

                           if we got both of it correct, then we know that both time isn't clashing

                           then how to know if they're clashing?

                           easy! we just negate `cond` to get the other one, example our current condition is true,
                           to get the other condition, just negate the `cond` using ! -> !cond
                         */
                        var cond = (stimesrc < stimedst && etimesrc <= stimedst) ||
                            (stimesrc >= etimedst && etimesrc > etimedst);

                        // if clashing, then return true
                        if(!cond) {
                            return true;
                        }
                    }
                }
            }
        }
    }

    return false;
}

function convertDate(time) {

    // find am/pm index (using only 'm' character)
    var index = time.indexOf("m");

    // compute real time length
    var getTime = time.substr(0, index - 1);

    // get hour & minute
    var getHour = parseFloat(getTime.substr(0, getTime.indexOf(':')));
    var getMinutes = getTime.substr(getTime.indexOf(':')+1, 2);

    // get either pm or am
    var dateIndi = time.substr(index - 1, 2);

    if (dateIndi === 'pm' && getHour != 12) {
        getHour += 12;
    }

    return parseFloat(getHour + '.' + getMinutes);
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

    var http = new XMLHttpRequest();

    http.open("POST", url, async);

    http.onloadstart = function (e) {
        document.querySelector('#loadingBox').style.display = 'block';
    };

    http.onreadystatechange = function () {

        document.querySelector('#loadingBox').style.display = 'none';

        if (this.readyState === 4) {
            if (this.status >= 200 && this.status < 400) {

                if (this.responseText.length === 0) {
                    alertify.delay(10000).error("Request return no data!\nNo internet connection?");
                } else {
                    alertify.delay(5000).success("Fetching data success!");
                    func(this.responseText)
                }
            } else {
                // Error :(
            }
        }
    };

    http.ontimeout = function () {
        alertify.delay(10000).error('Error request! No internet?');
    };

    if (postdata != '' && postdata != null) {
        // send the proper header information along with the request
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        // send POST request with out data
        http.send(postdata);

    } else {

        http.send();
    }
}

function parents(nodeCur, parentMatch) {

    var cur = nodeCur;
    for (; !cur.matches(parentMatch); cur = cur.parentNode) {
    }
    return cur;

}