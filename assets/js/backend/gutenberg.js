wp.blocks.registerBlockType( 'super-forms/form', {
    title: 'Super Forms',
    icon: 'universal-access-alt',
    category: 'layout',
    attributes: {
        content: {
            type: 'string',
            source: 'html',
            selector: 'p',
        }
    },
    edit: function(props) {
    	var content = props.attributes.content;
        function onChangeContent( newContent ) {
            props.setAttributes( { content: newContent } );
        }
        return wp.element.createElement(
            wp.editor.RichText,
            {
                tagName: 'p',
                className: props.className,
                onChange: onChangeContent,
                value: content,
            }
        );
    },
    save: function(props) {
        var content = props.attributes.content;
        return wp.element.createElement( wp.editor.RichText.Content, {
            tagName: 'p',
            className: props.className,
            value: content
        } );
    },
} );