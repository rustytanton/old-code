import $ from 'jquery';
import { bsp_utils } from '../temp/bsp-utils';
import example from '../utilities/example';

export default bsp_utils.plugin(false, 'bsp', 'example-plugin', {
    '_each': function(item) {
        var options = this.option(item);
        var moduleInstance = Object.create(example);
        moduleInstance.init($(item), options);
    }
});