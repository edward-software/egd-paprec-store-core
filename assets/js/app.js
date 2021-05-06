'use strict';

import '../scss/global.scss';

global.$ = global.jQuery = require('jquery');
import '../js/plugins/egd-datatable';
import '../js/plugins/egd-datatable-locales';
import 'datatables.net';
import 'datatables.net-buttons';
import 'datatables.net-buttons-bs4';
import 'datatables.net-select';
import 'datatables.net-select-bs4';
import 'datatables.net-bs4';
import 'datatables.net-dt';
import 'datatables.net-rowreorder';
import 'datatables.net-rowreorder-bs4';

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
import 'select2/dist/js/select2.min'
import 'bootstrap-select/dist/js/bootstrap-select.min'
import 'bootstrap-select/dist/js/i18n/defaults-fr_FR.min'
import 'bootstrap-select/dist/js/i18n/defaults-en_US.min'

import Swal from 'sweetalert2';
global.Swal = Swal;

import bootbox from 'bootbox';
global.bootbox = bootbox;
