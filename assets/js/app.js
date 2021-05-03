'use strict';

import '../scss/global.scss';

global.$ = global.jQuery = require('jquery');

const moment =  require('moment');
global.moment = moment;

import 'bootstrap';
import 'datatables.net';
import 'datatables.net-bs4';
import 'datatables.net-buttons';
import 'datatables.net-buttons-bs4';
import 'datatables.net-select';
import 'datatables.net-select-bs4';
import 'datatables.net-rowreorder';
import 'jquery-sortable';
import 'jquery-ui';
import 'jquery-ui/ui/widgets/sortable';
import 'jquery-ui/ui/widgets/autocomplete';
import 'jquery-ui/ui/disable-selection';
import 'eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min';

import Swal from 'sweetalert2';
global.Swal = Swal;
