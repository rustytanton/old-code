import $ from 'jquery';
import example from '../src/js/plugins/example';

describe('plugin', () => {

	beforeEach(() => {
		$('body').append('<div id="testContainer" data-bsp-example-plugin></div>');
	});

	afterEach(() => {
		$('#testContainer').remove();
	});

	it('should replace a test element with expected text', (done) => {
		setTimeout(() => {
			expect( $('#testContainer').html() ).toBe('<p>hello from ES6</p>');
			done();
		}, 50);
	});

});