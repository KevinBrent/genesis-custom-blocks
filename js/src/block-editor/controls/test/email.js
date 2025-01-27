/**
 * External dependencies
 */
import { fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GcbEmailControl from '../email';
import { setupControl } from './helpers';

/**
 * Gets the props for the tested component.
 *
 * @return {Object} The props to pass to the component.
 */
const getProps = () => {
	return {
		field: {
			label: 'Here is an example label',
			default: 'This is an example default value',
		},
		onChange: jest.fn(),
	};
};

describe( 'email control', () => {
	it( 'displays the default value if no value is entered', () => {
		const props = getProps();
		const { control } = setupControl( GcbEmailControl, props );
		expect( control ).toHaveValue( props.field.default );
	} );

	it.each( [
		'you@example.com',
		'not-a-valid-email',
		')$@$%*)#$*@)#$',
	] )( 'should send any entered text to the onChange handler, even if it is not a valid email',
		( enteredText ) => {
			const props = getProps();
			const { control } = setupControl( GcbEmailControl, props );
			fireEvent.change( control, { target: { value: enteredText } } );
			expect( props.onChange ).toHaveBeenLastCalledWith( enteredText );
		}
	);
} );
