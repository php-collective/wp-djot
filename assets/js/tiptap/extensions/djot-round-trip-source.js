import { Extension } from 'https://esm.sh/@tiptap/core@2';

export const DjotRoundTripSource = Extension.create({
    name: 'djotRoundTripSource',

    addGlobalAttributes() {
        return [
            {
                types: ['paragraph', 'heading'],
                attributes: {
                    djotSrc: {
                        default: null,
                        parseHTML: element => element.getAttribute('data-djot-src'),
                        renderHTML: () => ({}),
                    },
                    djotPlain: {
                        default: null,
                        parseHTML: element => element.getAttribute('data-djot-plain'),
                        renderHTML: () => ({}),
                    },
                },
            },
            {
                types: ['link'],
                attributes: {
                    djotAutolink: {
                        default: false,
                        parseHTML: element => element.getAttribute('data-djot-autolink') === '1',
                        renderHTML: () => ({}),
                    },
                },
            },
        ];
    },
});

export default DjotRoundTripSource;
