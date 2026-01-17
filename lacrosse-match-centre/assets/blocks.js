/**
 * Block Editor JavaScript for Lacrosse Match Centre
 */

(function(wp) {
    'use strict';
    
    if (!wp || !wp.blocks || !wp.element) {
        console.error('WordPress blocks not available');
        return;
    }
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor || wp.editor || {};
    const { PanelBody, TextControl, SelectControl, RangeControl, Placeholder } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el } = wp.element;
    
    console.log('Lacrosse Match Centre: Starting block registration...');
    
    if (!InspectorControls) {
        console.error('InspectorControls not available');
        return;
    }
    
    // Get competitions list from localized data
    const competitions = window.lmcBlockData && window.lmcBlockData.competitions ? window.lmcBlockData.competitions : [
        { label: '-- Use Current Competition --', value: '' }
    ];

    // Ladder Block
    registerBlockType('lacrosse-match-centre/ladder', {
        title: __('Lacrosse Ladder', 'lacrosse-match-centre'),
        icon: 'list-view',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Competition Ladder'
            },
            compId: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'lmc-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { 
                        title: __('Block Settings', 'lacrosse-match-centre'),
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Title', 'lacrosse-match-centre'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Competition', 'lacrosse-match-centre'),
                            help: __('Select a competition or use the current default', 'lacrosse-match-centre'),
                            value: attributes.compId,
                            options: competitions,
                            onChange: function(value) {
                                setAttributes({ compId: value });
                            }
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'list-view',
                    label: __('Lacrosse Ladder', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.title || 'Competition Ladder'),
                        el('p', null, __('Ladder will display here on the front-end', 'lacrosse-match-centre'))
                    )
                )
            );
        },
        
        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Ladder block registered');

    // Upcoming Games Block
    registerBlockType('lacrosse-match-centre/upcoming', {
        title: __('Upcoming Games', 'lacrosse-match-centre'),
        icon: 'calendar-alt',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Upcoming Games'
            },
            compId: {
                type: 'string',
                default: ''
            },
            limit: {
                type: 'number',
                default: 5
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'lmc-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { 
                        title: __('Block Settings', 'lacrosse-match-centre'),
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Title', 'lacrosse-match-centre'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Competition', 'lacrosse-match-centre'),
                            help: __('Select a competition or use the current default', 'lacrosse-match-centre'),
                            value: attributes.compId,
                            options: competitions,
                            onChange: function(value) {
                                setAttributes({ compId: value });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Number of Games', 'lacrosse-match-centre'),
                            value: attributes.limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 20
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'calendar-alt',
                    label: __('Upcoming Games', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.title || 'Upcoming Games'),
                        el('p', null, __('Showing ' + attributes.limit + ' upcoming games', 'lacrosse-match-centre'))
                    )
                )
            );
        },
        
        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Upcoming block registered');

    // Results Block
    registerBlockType('lacrosse-match-centre/results', {
        title: __('Match Results', 'lacrosse-match-centre'),
        icon: 'awards',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Recent Results'
            },
            compId: {
                type: 'string',
                default: ''
            },
            limit: {
                type: 'number',
                default: 5
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'lmc-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { 
                        title: __('Block Settings', 'lacrosse-match-centre'),
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Title', 'lacrosse-match-centre'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Competition', 'lacrosse-match-centre'),
                            help: __('Select a competition or use the current default', 'lacrosse-match-centre'),
                            value: attributes.compId,
                            options: competitions,
                            onChange: function(value) {
                                setAttributes({ compId: value });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Number of Results', 'lacrosse-match-centre'),
                            value: attributes.limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 20
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'awards',
                    label: __('Match Results', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.title || 'Recent Results'),
                        el('p', null, __('Showing ' + attributes.limit + ' recent results', 'lacrosse-match-centre'))
                    )
                )
            );
        },
        
        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Results block registered');
    
    // Team Results Block
    registerBlockType('lacrosse-match-centre/team-results', {
        title: __('Team Results', 'lacrosse-match-centre'),
        icon: 'awards',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Team Results'
            },
            compId: {
                type: 'string',
                default: ''
            },
            teamName: {
                type: 'string',
                default: ''
            },
            limit: {
                type: 'number',
                default: 5
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'lmc-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { 
                        title: __('Block Settings', 'lacrosse-match-centre'),
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Title', 'lacrosse-match-centre'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Competition', 'lacrosse-match-centre'),
                            help: __('Select a competition or use the current default', 'lacrosse-match-centre'),
                            value: attributes.compId,
                            options: competitions,
                            onChange: function(value) {
                                setAttributes({ compId: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Team Name (Optional)', 'lacrosse-match-centre'),
                            help: __('Leave empty to use the primary team from settings', 'lacrosse-match-centre'),
                            value: attributes.teamName,
                            placeholder: 'Use primary team',
                            onChange: function(value) {
                                setAttributes({ teamName: value });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Number of Results', 'lacrosse-match-centre'),
                            value: attributes.limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 20
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'awards',
                    label: __('Team Results', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.title || 'Team Results'),
                        el('p', null, 
                            attributes.teamName 
                                ? __('Showing results for ' + attributes.teamName, 'lacrosse-match-centre')
                                : __('Showing results for primary team', 'lacrosse-match-centre')
                        )
                    )
                )
            );
        },
        
        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Team Results block registered');
    
    // Team Upcoming Games Block
    registerBlockType('lacrosse-match-centre/team-upcoming', {
        title: __('Team Upcoming Games', 'lacrosse-match-centre'),
        icon: 'calendar-alt',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Team Upcoming Games'
            },
            compId: {
                type: 'string',
                default: ''
            },
            teamName: {
                type: 'string',
                default: ''
            },
            limit: {
                type: 'number',
                default: 5
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'lmc-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { 
                        title: __('Block Settings', 'lacrosse-match-centre'),
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Title', 'lacrosse-match-centre'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Competition', 'lacrosse-match-centre'),
                            help: __('Select a competition or use the current default', 'lacrosse-match-centre'),
                            value: attributes.compId,
                            options: competitions,
                            onChange: function(value) {
                                setAttributes({ compId: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Team Name (Optional)', 'lacrosse-match-centre'),
                            help: __('Leave empty to use the primary team from settings', 'lacrosse-match-centre'),
                            value: attributes.teamName,
                            placeholder: 'Use primary team',
                            onChange: function(value) {
                                setAttributes({ teamName: value });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Number of Games', 'lacrosse-match-centre'),
                            value: attributes.limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 20
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'calendar-alt',
                    label: __('Team Upcoming Games', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.title || 'Team Upcoming Games'),
                        el('p', null, 
                            attributes.teamName 
                                ? __('Showing games for ' + attributes.teamName, 'lacrosse-match-centre')
                                : __('Showing games for primary team', 'lacrosse-match-centre')
                        )
                    )
                )
            );
        },
        
        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Team Upcoming block registered');
    console.log('Lacrosse Match Centre: All blocks registered successfully!');

})(window.wp);
