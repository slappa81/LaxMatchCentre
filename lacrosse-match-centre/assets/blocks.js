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
    const { PanelBody, TextControl, SelectControl, RangeControl, Placeholder, ToggleControl } = wp.components;
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
            },
            displayMode: {
                type: 'string',
                default: 'text'
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
                        el(SelectControl, {
                            label: __('Display Mode', 'lacrosse-match-centre'),
                            help: __('Show team logos, names, or both', 'lacrosse-match-centre'),
                            value: attributes.displayMode,
                            options: [
                                { label: 'Team Names Only', value: 'text' },
                                { label: 'Team Logos Only', value: 'image' },
                                { label: 'Both Logo and Name', value: 'both' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayMode: value });
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

    // Competition Selector Block
    registerBlockType('lacrosse-match-centre/competition-selector', {
        title: __('Competition Selector', 'lacrosse-match-centre'),
        icon: 'filter',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Competition'
            },
            showLabel: {
                type: 'boolean',
                default: true
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
                            label: __('Label', 'lacrosse-match-centre'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Label', 'lacrosse-match-centre'),
                            checked: attributes.showLabel,
                            onChange: function(value) {
                                setAttributes({ showLabel: value });
                            }
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'filter',
                    label: __('Competition Selector', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.showLabel ? (attributes.title || 'Competition') : __('Competition Selector', 'lacrosse-match-centre')),
                        el('p', null, __('Visitors can switch competitions on the front-end', 'lacrosse-match-centre'))
                    )
                )
            );
        },

        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Competition selector block registered');

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
            },
            displayMode: {
                type: 'string',
                default: 'text'
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
                        }),
                        el(SelectControl, {
                            label: __('Display Mode', 'lacrosse-match-centre'),
                            help: __('Show team logos, names, or both', 'lacrosse-match-centre'),
                            value: attributes.displayMode,
                            options: [
                                { label: 'Team Names Only', value: 'text' },
                                { label: 'Team Logos Only', value: 'image' },
                                { label: 'Both Logo and Name', value: 'both' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayMode: value });
                            }
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
            },
            displayMode: {
                type: 'string',
                default: 'text'
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
                        }),
                        el(SelectControl, {
                            label: __('Display Mode', 'lacrosse-match-centre'),
                            help: __('Show team logos, names, or both', 'lacrosse-match-centre'),
                            value: attributes.displayMode,
                            options: [
                                { label: 'Team Names Only', value: 'text' },
                                { label: 'Team Logos Only', value: 'image' },
                                { label: 'Both Logo and Name', value: 'both' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayMode: value });
                            }
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

    // Results + Upcoming Block
    registerBlockType('lacrosse-match-centre/results-upcoming', {
        title: __('Results & Upcoming', 'lacrosse-match-centre'),
        icon: 'schedule',
        category: 'lacrosse-match-centre',
        attributes: {
            title: {
                type: 'string',
                default: 'Results & Upcoming'
            },
            compId: {
                type: 'string',
                default: ''
            },
            resultsLimit: {
                type: 'number',
                default: 3
            },
            upcomingLimit: {
                type: 'number',
                default: 3
            },
            cardsPerView: {
                type: 'number',
                default: 4
            },
            displayMode: {
                type: 'string',
                default: 'text'
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
                            value: attributes.resultsLimit,
                            onChange: function(value) {
                                setAttributes({ resultsLimit: value });
                            },
                            min: 1,
                            max: 20
                        }),
                        el(RangeControl, {
                            label: __('Number of Upcoming Games', 'lacrosse-match-centre'),
                            value: attributes.upcomingLimit,
                            onChange: function(value) {
                                setAttributes({ upcomingLimit: value });
                            },
                            min: 1,
                            max: 20
                        }),
                        el(SelectControl, {
                            label: __('Cards Per View', 'lacrosse-match-centre'),
                            value: String(attributes.cardsPerView),
                            options: [
                                { label: '3', value: '3' },
                                { label: '4', value: '4' },
                                { label: '5', value: '5' }
                            ],
                            onChange: function(value) {
                                setAttributes({ cardsPerView: parseInt(value, 10) });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Display Mode', 'lacrosse-match-centre'),
                            help: __('Show team logos, names, or both', 'lacrosse-match-centre'),
                            value: attributes.displayMode,
                            options: [
                                { label: 'Team Names Only', value: 'text' },
                                { label: 'Team Logos Only', value: 'image' },
                                { label: 'Both Logo and Name', value: 'both' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayMode: value });
                            }
                        })
                    )
                ),
                el(Placeholder, {
                    icon: 'schedule',
                    label: __('Results & Upcoming', 'lacrosse-match-centre')
                },
                    el('div', { style: { textAlign: 'center' } },
                        el('strong', null, attributes.title || 'Results & Upcoming'),
                        el('p', null, __('Showing ' + attributes.resultsLimit + ' results and ' + attributes.upcomingLimit + ' upcoming games', 'lacrosse-match-centre'))
                    )
                )
            );
        },

        save: function() {
            return null;
        }
    });

    console.log('Lacrosse Match Centre: Results/Upcoming block registered');
    
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
            },
            displayMode: {
                type: 'string',
                default: 'text'
            },
            allowCompSync: {
                type: 'boolean',
                default: true
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
                        }),
                        el(SelectControl, {
                            label: __('Display Mode', 'lacrosse-match-centre'),
                            help: __('Choose how to display team names', 'lacrosse-match-centre'),
                            value: attributes.displayMode,
                            options: [
                                { label: 'Text Only', value: 'text' },
                                { label: 'Image Only', value: 'image' },
                                { label: 'Both', value: 'both' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayMode: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Sync with competition selector', 'lacrosse-match-centre'),
                            checked: attributes.allowCompSync,
                            onChange: function(value) {
                                setAttributes({ allowCompSync: value });
                            }
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
            },
            displayMode: {
                type: 'string',
                default: 'text'
            },
            allowCompSync: {
                type: 'boolean',
                default: true
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
                        }),
                        el(SelectControl, {
                            label: __('Display Mode', 'lacrosse-match-centre'),
                            help: __('Choose how to display team names', 'lacrosse-match-centre'),
                            value: attributes.displayMode,
                            options: [
                                { label: 'Text Only', value: 'text' },
                                { label: 'Image Only', value: 'image' },
                                { label: 'Both', value: 'both' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayMode: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Sync with competition selector', 'lacrosse-match-centre'),
                            checked: attributes.allowCompSync,
                            onChange: function(value) {
                                setAttributes({ allowCompSync: value });
                            }
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
