(function ($) {
    'use strict';

    var data = window.lmcAdminData || {};
    var nonces = data.nonces || {};
    var ajaxUrl = (typeof window.ajaxurl !== 'undefined' && window.ajaxurl) || data.ajaxUrl || '';
    var STATUS_COLORS = {
        success: '#1a7f37',
        error: '#b32d2e',
        info: '#2271b1'
    };

    function setStatus($target, type, message) {
        if (!$target || !$target.length) {
            return;
        }
        var color = STATUS_COLORS[type] || STATUS_COLORS.info;
        $target.html('<span style="color:' + color + ';">' + message + '</span>');
    }

    function toggleButton($btn, isBusy, busyText) {
        if (!$btn || !$btn.length) {
            return;
        }
        if (isBusy) {
            if (!$btn.data('originalText')) {
                $btn.data('originalText', $btn.text());
            }
            if (busyText) {
                $btn.text(busyText);
            }
            $btn.prop('disabled', true);
        } else {
            var original = $btn.data('originalText');
            if (typeof original !== 'undefined') {
                $btn.text(original);
            }
            $btn.prop('disabled', false);
        }
    }

    function ajaxRequest(payload, options) {
        if (!ajaxUrl) {
            return $.Deferred().reject().promise();
        }
        return $.ajax($.extend({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: payload
        }, options || {}));
    }

    function addCompetitionRow(compId, compTitle, seasonLabel) {
        var template = $('#competition-template').html();
        if (!template) {
            return null;
        }
        var index = $('.lmc-competition-row').length;
        var html = template.replace(/INDEX/g, index);
        $('#lmc-competitions-list').append(html);
        var $row = $('.lmc-competition-row').last();
        if (compId) {
            $row.find('.comp-id').val(compId);
        }
        if (compTitle) {
            $row.find('.comp-name').val(compTitle);
        }
        if (seasonLabel) {
            $row.find('.comp-season').val(seasonLabel);
        }
        return $row;
    }

    function highlightRow($row) {
        if (!$row || !$row.length) {
            return;
        }
        $row.css('background-color', '#ffffcc');
        setTimeout(function () {
            $row.css('background-color', '#fff');
        }, 1500);
    }

    $(function () {
        if (!ajaxUrl) {
            console.error('LMC Admin: ajaxurl is not available.');
            return;
        }

        // Remove competition rows
        $(document).on('click', '.lmc-remove-competition', function () {
            $(this).closest('.lmc-competition-row').remove();
        });

        // Scrape data for a competition
        $(document).on('click', '.lmc-scrape-btn', function () {
            var $btn = $(this);
            var $row = $btn.closest('.lmc-competition-row');
            var compId = $row.find('.comp-id').val() || $row.find('input[name*="[id]"]').val();
            var compName = $row.find('.comp-name').val() || $row.find('input[name*="[name]"]').val();
            var $status = $row.find('.scrape-status');

            if (!compId || !compName) {
                window.alert('Please provide both the competition ID and name before scraping.');
                return;
            }

            toggleButton($btn, true, 'Scraping...');
            setStatus($status, 'info', 'Scraping data. This can take a couple of minutes...');

            ajaxRequest({
                action: 'lmc_scrape_competition',
                nonce: nonces.scrape,
                comp_id: compId,
                comp_name: compName
            }, { timeout: 300000 }).done(function (response) {
                if (response && response.success) {
                    var message = (response.data && response.data.message) ? response.data.message : 'Scrape completed.';
                    setStatus($status, 'success', message);
                } else {
                    var errorMsg = (response && response.data && response.data.message) || (response && response.message) || 'Scraping failed.';
                    setStatus($status, 'error', errorMsg);
                }
            }).fail(function (jqXHR, textStatus) {
                if (textStatus === 'timeout') {
                    setStatus($status, 'error', 'Request timed out. Please check the debug log for progress.');
                } else {
                    var fallback = 'Request failed.';
                    if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                        fallback = jqXHR.responseJSON.data.message;
                    }
                    setStatus($status, 'error', fallback);
                }
            }).always(function () {
                toggleButton($btn, false);
            });
        });

        // Clear all cached data
        $('#lmc-clear-cache').on('click', function () {
            var $btn = $(this);
            if (!window.confirm('Clear all cached data?')) {
                return;
            }

            toggleButton($btn, true, 'Clearing...');
            ajaxRequest({
                action: 'lmc_clear_cache',
                nonce: nonces.cache
            }).done(function (response) {
                if (response && response.success) {
                    window.alert('Cache cleared successfully.');
                } else {
                    var msg = (response && response.data && response.data.message) || 'Unable to clear cache.';
                    window.alert(msg);
                }
            }).fail(function () {
                window.alert('Request failed while clearing the cache.');
            }).always(function () {
                toggleButton($btn, false);
            });
        });

        // Discover seasons
        $('#lmc-discover-seasons-btn').on('click', function () {
            var $btn = $(this);
            var associationId = $.trim($('#lmc-discover-association-id').val());
            var $status = $('#lmc-discover-status');
            var $seasons = $('#lmc-seasons-selection');
            var $results = $('#lmc-discover-results');

            if (!associationId) {
                setStatus($status, 'error', 'Please enter an Association ID.');
                return;
            }

            toggleButton($btn, true, 'Loading...');
            setStatus($status, 'info', 'Fetching seasons from GameDay...');
            $seasons.empty();
            $results.empty();

            ajaxRequest({
                action: 'lmc_list_seasons',
                nonce: nonces.listCompetitions,
                association_id: associationId
            }, { timeout: 30000 }).done(function (response) {
                toggleButton($btn, false);

                if (response && response.success && response.data && Array.isArray(response.data.seasons) && response.data.seasons.length) {
                    var seasons = response.data.seasons;
                    setStatus($status, 'success', 'Found ' + seasons.length + ' season(s). Select one to continue.');

                    var $wrapper = $('<div/>', { class: 'lmc-season-picker', css: { margin: '15px 0' } });
                    var $select = $('<select/>', {
                        id: 'lmc-season-select',
                        class: 'regular-text',
                        css: { marginRight: '10px' }
                    });
                    $select.append($('<option/>', { value: '', text: '-- Select a Season --' }));
                    seasons.forEach(function (season) {
                        $select.append($('<option/>', { value: season.id, text: season.name }));
                    });

                    var $loadButton = $('<button/>', {
                        type: 'button',
                        id: 'lmc-load-competitions-btn',
                        class: 'button',
                        text: 'Load Competitions'
                    });

                    $wrapper.append($select).append($loadButton);
                    $seasons.empty().append($wrapper);
                } else {
                    var message = (response && response.data && response.data.message) || 'No seasons found.';
                    setStatus($status, 'error', message);
                }
            }).fail(function () {
                toggleButton($btn, false);
                setStatus($status, 'error', 'Request failed while loading seasons.');
            });
        });

        // Load competitions for a season
        $(document).on('click', '#lmc-load-competitions-btn', function () {
            var $btn = $(this);
            var associationId = $.trim($('#lmc-discover-association-id').val());
            var seasonId = $('#lmc-season-select').val();
            var seasonName = $('#lmc-season-select option:selected').text();
            var $status = $('#lmc-discover-status');
            var $results = $('#lmc-discover-results');

            if (!seasonId) {
                setStatus($status, 'error', 'Please select a season.');
                return;
            }

            toggleButton($btn, true, 'Loading...');
            setStatus($status, 'info', 'Fetching competitions for the selected season...');
            $results.empty();

            ajaxRequest({
                action: 'lmc_list_available_competitions',
                nonce: nonces.listCompetitions,
                association_id: associationId,
                season_id: seasonId,
                season_name: seasonName
            }, { timeout: 30000 }).done(function (response) {
                toggleButton($btn, false);

                if (response && response.success && response.data && Array.isArray(response.data.competitions) && response.data.competitions.length) {
                    var competitions = response.data.competitions;
                    setStatus($status, 'success', 'Found ' + competitions.length + ' competition(s). Select the ones you need.');

                    var $container = $('<div/>', {
                        css: {
                            marginTop: '15px',
                            border: '1px solid #ccc',
                            padding: '15px',
                            background: '#f9f9f9'
                        }
                    });

                    var $actions = $('<div/>', { css: { marginBottom: '10px' } });
                    $actions.append($('<button/>', { type: 'button', id: 'lmc-select-all-comps', class: 'button button-small', text: 'Select All', css: { marginRight: '5px' } }));
                    $actions.append($('<button/>', { type: 'button', id: 'lmc-deselect-all-comps', class: 'button button-small', text: 'Deselect All', css: { marginRight: '5px' } }));
                    $actions.append($('<button/>', { type: 'button', id: 'lmc-add-selected-comps', class: 'button button-primary', text: 'Add Selected Competitions' }));

                    var $list = $('<div/>', {
                        css: {
                            maxHeight: '400px',
                            overflowY: 'auto',
                            background: '#fff',
                            padding: '10px',
                            border: '1px solid #ddd'
                        }
                    });

                    competitions.forEach(function (comp) {
                        var $row = $('<div/>', { css: { margin: '8px 0', padding: '8px', borderBottom: '1px solid #eee' } });
                        var $label = $('<label/>', { css: { display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer' } });
                        var $checkbox = $('<input/>', {
                            type: 'checkbox',
                            class: 'lmc-comp-checkbox',
                            'data-id': comp.id,
                            'data-name': comp.name,
                            'data-season': seasonName
                        });
                        var $name = $('<span/>', { css: { flex: '1', fontWeight: '600' }, text: comp.name });
                        var $code = $('<code/>', { css: { fontSize: '11px', color: '#666' }, text: comp.id });

                        $label.append($checkbox).append($name).append($code);
                        $row.append($label);
                        $list.append($row);
                    });

                    $container.append($actions).append($list);
                    $results.empty().append($container);
                } else {
                    var message = (response && response.data && response.data.message) || 'No competitions found.';
                    setStatus($status, 'error', message);
                }
            }).fail(function () {
                toggleButton($btn, false);
                setStatus($status, 'error', 'Request failed while loading competitions.');
            });
        });

        $(document).on('click', '#lmc-select-all-comps', function () {
            $('.lmc-comp-checkbox').prop('checked', true);
        });

        $(document).on('click', '#lmc-deselect-all-comps', function () {
            $('.lmc-comp-checkbox').prop('checked', false);
        });

        $(document).on('click', '#lmc-add-selected-comps', function () {
            var $checked = $('.lmc-comp-checkbox:checked');

            if ($checked.length === 0) {
                window.alert('Please select at least one competition.');
                return;
            }

            $checked.each(function () {
                var $item = $(this);
                var compId = $item.data('id');
                var compName = $item.data('name');
                var seasonName = $item.data('season');
                var rowTitle = seasonName ? seasonName + ' - ' + compName : compName;
                var $row = addCompetitionRow(compId, rowTitle, seasonName);
                highlightRow($row);
            });

            $('#lmc-discover-results').empty();
            setStatus($('#lmc-discover-status'), 'success', 'Added ' + $checked.length + ' competition(s) to the configuration.');
        });

        // Legacy "Use This" button support
        $(document).on('click', '.lmc-use-competition', function () {
            var $btn = $(this);
            var compId = $btn.data('id');
            var compName = $btn.data('name');
            var $row = addCompetitionRow(compId, compName);
            highlightRow($row);
            if ($row) {
                $('html, body').animate({ scrollTop: $row.offset().top - 100 }, 300);
            }
            window.alert('Competition added. Remember to save your settings.');
        });

        // Load teams for a competition
        $(document).on('click', '.lmc-get-teams-btn', function () {
            var $btn = $(this);
            var $row = $btn.closest('.lmc-competition-row');
            var compId = $row.find('.comp-id').val() || $row.find('input[name*="[id]"]').val();
            var $teamSelect = $row.find('.lmc-team-select');
            var $teamStatus = $row.find('.lmc-team-status');

            if (!compId) {
                window.alert('Competition ID not found.');
                return;
            }

            var selectedTeams = $teamSelect.val();
            if (!Array.isArray(selectedTeams)) {
                selectedTeams = selectedTeams ? [selectedTeams] : [];
            }

            toggleButton($btn, true, 'Loading Teams...');
            setStatus($teamStatus, 'info', 'Fetching teams from the scraped data...');

            ajaxRequest({
                action: 'lmc_get_teams',
                nonce: nonces.teams,
                comp_id: compId
            }).done(function (response) {
                if (response && response.success && response.data && Array.isArray(response.data.teams) && response.data.teams.length) {
                    $teamSelect.empty();
                    $teamSelect.append($('<option/>', { value: '', text: '-- Select Primary Team(s) --' }));
                    response.data.teams.forEach(function (team) {
                        var $option = $('<option/>', { value: team, text: team });
                        if (selectedTeams.indexOf(team) !== -1) {
                            $option.prop('selected', true);
                        }
                        $teamSelect.append($option);
                    });
                    $teamSelect.show();
                    setStatus($teamStatus, 'success', 'Found ' + response.data.teams.length + ' team(s).');
                } else {
                    var message = (response && response.data && response.data.message) || 'No teams found. Please scrape the competition first.';
                    setStatus($teamStatus, 'error', message);
                }
            }).fail(function () {
                setStatus($teamStatus, 'error', 'Request failed while loading teams.');
            }).always(function () {
                toggleButton($btn, false);
                $btn.text('Refresh Teams');
            });
        });

        // Upload custom team logos
        $(document).on('click', '.lmc-upload-logo-btn', function () {
            var $btn = $(this);
            var teamKey = $btn.data('team');
            var teamName = $btn.data('team-name');

            if (!teamKey) {
                return;
            }

            var frame = wp.media({
                title: 'Select Team Logo for ' + teamName,
                button: { text: 'Use this image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first();
                if (!attachment) {
                    return;
                }

                var image = attachment.toJSON();
                toggleButton($btn, true, 'Saving...');

                ajaxRequest({
                    action: 'lmc_upload_team_logo',
                    nonce: nonces.admin,
                    team: teamKey,
                    image_url: image.url
                }).done(function (response) {
                    if (response && response.success) {
                        window.location.reload();
                    } else {
                        var message = (response && response.data) || 'Unable to save the logo.';
                        window.alert(message);
                    }
                }).fail(function () {
                    window.alert('Request failed while uploading the logo.');
                }).always(function () {
                    toggleButton($btn, false);
                });
            });

            frame.open();
        });

        // Delete custom logos
        $(document).on('click', '.lmc-delete-logo-btn', function () {
            var $btn = $(this);
            var teamKey = $btn.data('team');

            if (!teamKey) {
                return;
            }

            if (!window.confirm('Remove the custom logo and fall back to the scraped version?')) {
                return;
            }

            toggleButton($btn, true, 'Removing...');

            ajaxRequest({
                action: 'lmc_delete_team_logo',
                nonce: nonces.admin,
                team: teamKey
            }).done(function (response) {
                if (response && response.success) {
                    window.location.reload();
                } else {
                    var message = (response && response.data) || 'Unable to remove the custom logo.';
                    window.alert(message);
                }
            }).fail(function () {
                window.alert('Request failed while removing the logo.');
            }).always(function () {
                toggleButton($btn, false);
            });
        });

        // Clear cached logos
        $('#lmc-clear-cached-logos').on('click', function () {
            var $btn = $(this);
            var $status = $('#lmc-clear-logos-status');

            if (!window.confirm('Delete all locally cached team logos? They will be re-downloaded on the next scrape.')) {
                return;
            }

            toggleButton($btn, true, 'Clearing...');
            setStatus($status, 'info', 'Clearing cached logos...');

            ajaxRequest({
                action: 'lmc_clear_cached_logos',
                nonce: nonces.admin
            }).done(function (response) {
                if (response && response.success) {
                    setStatus($status, 'success', 'Cached logos cleared successfully. Reloading...');
                    setTimeout(function () {
                        window.location.reload();
                    }, 1500);
                } else {
                    var message = (response && response.data) || 'Unable to clear cached logos.';
                    setStatus($status, 'error', message);
                }
            }).fail(function () {
                setStatus($status, 'error', 'Request failed while clearing cached logos.');
            }).always(function () {
                toggleButton($btn, false);
            });
        });
    });
})(jQuery);
