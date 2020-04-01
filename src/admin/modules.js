import Vue from 'vue';
import AddOnModules from './views/AddonModules';

import {
    Button,
    Select,
    Input,
    Switch,
    Message
} from 'element-ui';

Vue.use(Button);
Vue.use(Select); 
Vue.use(Input);
Vue.use(Switch);

Vue.prototype.$message = Message;

var app = new Vue({
  el: '#payformAddonModules',
  components: {
    'pay_form_addon_modules' : AddOnModules
  },
})