( function( blocks, i18n, element, components, editor ) {

    var el = element.createElement;
    var __ = i18n.__;
    var iconEl = el('svg', {xmlns: "http://www.w3.org/2000/svg", width: "25px", height: "25px", viewBox: "0 0 160 160", className:  "paytium-shortcode-icon"},
        el('image', { href: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciID48ZGVmcz48c3R5bGU+LmJiYjhmNTdjLWI3NmQtNDg1OC04ZjFmLTU4MTA2YTllMGMxZHtmaWxsOiNmZmY7c3Ryb2tlOiMwMDA7c3Ryb2tlLW1pdGVybGltaXQ6MTA7c3Ryb2tlLXdpZHRoOjJweDt9LmVhYWY5MGQzLTVkZTMtNDUyNy1hMmEwLTJiOGUwYTM3ODA3YXtmaWxsOiMwYTBiMDk7fS5lYWFmOTBkMy01ZGUzLTQ1MjctYTJhMC0yYjhlMGEzNzgwN2EsLmVjNmUxMGYwLWYxNmQtNGU1Ni04NDI3LTM4Mjg5NmMxNGVkN3tmaWxsLXJ1bGU6ZXZlbm9kZDt9LmVjNmUxMGYwLWYxNmQtNGU1Ni04NDI3LTM4Mjg5NmMxNGVkN3tmaWxsOiNkNTAxNzI7fTwvc3R5bGU+PC9kZWZzPjx0aXRsZT5sb2dvX0Fzc2V0IDEtMDwvdGl0bGU+PGcgaWQ9ImY4MmM4NWYxLTI3NGYtNGQ0YS1hMjQ1LWYxZjUzNzRiYzdkYiIgZGF0YS1uYW1lPSJMYXllciAyIj48ZyBpZD0iYTAwYjNjY2UtM2MxMy00NDM3LTliYzQtNDM3OWNlZmQ4NmI1IiBkYXRhLW5hbWU9IkxheWVyIDEiPjxnIGlkPSJiZTI3OTc0Yy1hMWU5LTRkOTktODUzMi05YjYxOGNjYTY4YjgiIGRhdGEtbmFtZT0iUGFnZS0xIj48ZyBpZD0iYjVhNjBjYzEtY2ZjNy00Zjc3LTk1NjMtMTcwNWNlMTFjNDA2IiBkYXRhLW5hbWU9IkxvZ28taURlYWwiPjxwYXRoIGlkPSJmYjc3MDZmNi01NmY0LTQ5YzYtYTgxMy05MTJlNDM5NWUwODkiIGRhdGEtbmFtZT0iRmlsbC00IiBjbGFzcz0iYmJiOGY1N2MtYjc2ZC00ODU4LThmMWYtNTgxMDZhOWUwYzFkIiBkPSJNNzUuNiwxLjE3YzYwLjksMCw3MCwzOSw3MCw2Mi4zLDAsNDAuNC0yNC45LDYyLjYtNzAsNjIuNkgxVjEuMDdDMi40LDEuMTcsNzUuNiwxLjE3LDc1LjYsMS4xN1oiLz48cG9seWdvbiBpZD0iYmM5NDUzZTMtMGZjNi00MjY2LWIyNWItYzA2MWM0YzhmYTRlIiBkYXRhLW5hbWU9IkZpbGwtNSIgY2xhc3M9ImVhYWY5MGQzLTVkZTMtNDUyNy1hMmEwLTJiOGUwYTM3ODA3YSIgcG9pbnRzPSIxNi4zIDExMS4yNyAzOC4zIDExMS4yNyAzOC4zIDcxLjk3IDE2LjMgNzEuOTcgMTYuMyAxMTEuMjciLz48cGF0aCBpZD0iYjQ3MWRlOGEtY2QyOC00OWFlLTkyMGEtZGI2M2ZiMDZiODhlIiBkYXRhLW5hbWU9IkZpbGwtNiIgY2xhc3M9ImVhYWY5MGQzLTVkZTMtNDUyNy1hMmEwLTJiOGUwYTM3ODA3YSIgZD0iTTQxLDUyLjI3YTEzLjcsMTMuNywwLDEsMS0xMy43LTEzLjdBMTMuNjYsMTMuNjYsMCwwLDEsNDEsNTIuMjciLz48cGF0aCBpZD0iYTk2MTY2MjUtMWM1Yy00ZmUwLTk1MzMtNTAyN2QwOTc3ODY3IiBkYXRhLW5hbWU9IkZpbGwtNyIgY2xhc3M9ImVjNmUxMGYwLWYxNmQtNGU1Ni04NDI3LTM4Mjg5NmMxNGVkNyIgZD0iTTEzMC4zLDU4LjY3Yy0yLjYtMzQuNy0yOS45LTQyLjItNTQuNy00Mi4ySDQ5LjJ2OTQuN0g3NS43YzQwLjMsMCw1NC4zLTE4LjgsNTQuOS00Ni4yQTUzLjU3LDUzLjU3LDAsMCwwLDEzMC4zLDU4LjY3WiIvPjwvZz48L2c+PC9nPjwvZz48L3N2Zz4K" } )
    );

    blocks.registerBlockType( 'paytium/shortcode', {
        title: __( 'Paytium Code' ),
        icon: iconEl,
        category: 'widgets',
        keywords: ['ideal', 'payments', 'betaling'], // 3 keywords is a maximum: http://prntscr.com/m30m99
        attributes: {
            text: {
                type: 'string',
                source: 'text'
            },
            selectedOption: {
                type: 'string',
                source: 'selectedOption'
            },
        },

        supports: {
            customClassName: false,
            className: false,
            html: false
        },
        edit: function( props ) {
            var attributes = props.attributes,
                setAttributes = props.setAttributes,
                label = el("label", null,
                    __('Select an example form to get started:')
                ),
                shortcode = '',
                localContent = '',
                blankForm = el(components.Button, {
                        className:'blank-start',
                        onClick: function blank() {
                            return setAttributes({
                                text: shortcode,
                                selectedOption: 8
                            });
                        }
                    },
                    __('click here'),
                ),
                description = __(' to start from scratch without an example.' +
                ' View all examples in the <a href="https://www.paytium.nl/handleiding/voorbeelden/" class="select-paytium-form-manual" target="_blank">manual</a>.');

            if ( Object.keys(attributes).length === 0 && attributes.constructor === Object) {

                localContent = el("div", {className: "wp-block-paytium-from-select"},
                    el(components.RadioControl, {
                        className: 'select-paytium-form',
                        label: label,
                        value: attributes.selectedOption,
                        options: [
                            { label: 'Simple product or donation, static amount', value: 1 },
                            { label: 'Simple product or donation, open amount', value: 2 },
                            { label: 'Products with a quantity option', value: 3 },
                            { label: 'Dropdown with multiple amounts', value: 4 },
                            { label: 'Radio buttons with multiple amounts', value: 5 },
                            { label: 'Simple form with required email address', value: 6 },
                            { label: 'Extended form with name, email and address fields', value: 7 },
                            { label: 'Subscription/recurring payment', value: 8 },
                        ],
                        onChange: function onChange(text) {
                            switch (parseInt(text)) {
                                case 1:
                                    shortcode = '[paytium name="Form name" description="Payment description"]' +
                                                '\n[paytium_field type="label" label="€19,95" amount="19,95" /]'+
                                                '\n[paytium_button label="Pay" /]'+
                                                '\n[/paytium]';
                                    break;
                                case 2:
                                    shortcode = '[paytium name="Form name" description="Donations"]' +
                                                '\n[paytium_field type="open" label="Donation Amount:" default="25" /]' +
                                                '\n[paytium_total label="Donate" /]' +
                                                '\n[paytium_button label="Donate" /]' +
                                                '\n[/paytium]';
                                    break;
                                case 3:
                                    shortcode = '[paytium name="Form name" description="Payment description"]' +
                                                '\n[paytium_field type="label" label="Workshop tickets" amount="19.95" quantity="true" /]' +
                                                '\n[paytium_field type="label" label="T-shirts" amount="49.95" quantity="true" /]' +
                                                '\n[paytium_total label="Total" /]' +
                                                '\n[paytium_button label="Order now" /]' +
                                                '\n[/paytium]';
                                    break;
                                case 4:
                                    shortcode = '[paytium name="Form name" description="Payment description"]' +
                                                '\n[paytium_field type="dropdown" label="Options" options="9,95/19,95/29,95" options_are_amounts="true" /]' +
                                                '\n[paytium_total /]' +
                                                '\n[paytium_button label="Pay" /]' +
                                                '\n[/paytium]';
                                    break;
                                case 5:
                                    shortcode = '[paytium name="Form name" description="Payment description"]' +
                                                '\n[paytium_field type="radio" label="Options" options="9,95/19,95/29,95" options_are_amounts="true" /]' +
                                                '\n[paytium_total /]' +
                                                '\n[paytium_button label="Pay" /]' +
                                                '\n[/paytium]';
                                    break;
                                case 6:
                                    shortcode = '[paytium name="Form name" description="Payment description"]' +
                                                '\n[paytium_field type="email" label="Your email" required="true" /]' +
                                                '\n[paytium_field type="label" label="Product ABC for €19,95" amount="19,95" /]' +
                                                '\n[paytium_total /]' +
                                                '\n[paytium_button label="Pay" /]' +
                                                '\n[/paytium]';
                                    break;
                                case 7:
                                    shortcode = '[paytium name="Form name" description="Payment description"]' +
                                                '\n[paytium_field type="email" label="Email" required="true" /]' +
                                                '\n[paytium_field type="name" label="Name" required="true" /]' +
                                                '\n[paytium_field type="text" label="Address" required="true" /]' +
                                                '\n[paytium_field type="text" label="Postcode" required="true" /]' +
                                                '\n[paytium_field type="text" label="City" required="true" /]' +
                                                '\n[paytium_field type="text" label="Country" required="true" /]' +
                                                '\n[paytium_field type="label" label="Product ABC for €19,95" amount="19,95" /]' +
                                                '\n[paytium_total /]' +
                                                '\n[paytium_button label="Pay" /]' +
                                                '\n[/paytium]';
                                    break;
                                case 8:
                                    shortcode = '[paytium name="Subscription store" description="Some subscription"]' +
                                                '\n[paytium_subscription interval="1 days" times="99" /]' +
                                                '\n[paytium_field type="name" label="Volledige naam" /]' +
                                                '\n[paytium_field type="email" label="Jouw email" required="true" /]' +
                                                '\n[paytium_field type="label" label="Subscription €99" amount="99" /]' +
                                                '\n[paytium_total /]' +
                                                '\n[paytium_button label="Subscribe" /]' +
                                                '\n[/paytium]';
                                    break;
                                default:
                            }
                            return setAttributes({
                                text: shortcode,
                                selectedOption: text
                            });
                        }

                    }),
                    el("p", {className: 'select-paytium-form-description'},
                        el("span", {className: 'select-paytium-form-description'},
                            __('Or ')
                        ),
                        blankForm,
                        el("span", {className: 'select-paytium-form-description',dangerouslySetInnerHTML: { __html: description }})
                    ),
                )
            }
            else {
                localContent = el("div", {className: "wp-block-shortcode"},
                    el("label", null,
                        iconEl,
                        __('Paytium Code:')
                    ),
                    el(wp.blockEditor.PlainText, {
                        className: "input-control",
                        value: attributes.text,
                        placeholder: __('Write shortcode here…'),
                        onChange: function onChange(text) {
                            return setAttributes({
                                text: text
                            });
                        }
                    }),
                )
            }
            return (localContent)
        },
        save: function(props) {
            var attributes = props.attributes;
            return el(element.RawHTML, null, attributes.text);
        }
    } );
}(
    window.wp.blocks,
    window.wp.i18n,
    window.wp.element,
    window.wp.components,
    window.wp.editor
) );
