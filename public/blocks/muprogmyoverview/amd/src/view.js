// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Manage the programs view for the overview block.
 *
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as Repository from 'block_muprogmyoverview/repository';
import * as PagedContentFactory from 'core/paged_content_factory';
import * as PubSub from 'core/pubsub';
import * as CustomEvents from 'core/custom_interaction_events';
import * as Notification from 'core/notification';
import * as Templates from 'core/templates';
import SELECTORS from 'block_muprogmyoverview/selectors';
import * as PagedContentEvents from 'core/paged_content_events';
import * as Aria from 'core/aria';
import {debounce} from 'core/utils';
import {setUserPreference} from 'core_user/repository';

const TEMPLATES = {
    PROGRAMS_CARDS: 'block_muprogmyoverview/view-cards',
    PROGRAMS_LIST: 'block_muprogmyoverview/view-list',
    PROGRAMS_DESCRIPTION: 'block_muprogmyoverview/view-description',
    NOPROGRAMS: 'block_muprogmyoverview/no-programs'
};

const GROUPINGS = {
    GROUPING_ALLINCLUDINGHIDDEN: 'allincludinghidden',
    GROUPING_ALL: 'all',
    GROUPING_INPROGRESS: 'inprogress',
    GROUPING_FUTURE: 'future',
    GROUPING_PAST: 'past',
    GROUPING_FAVOURITES: 'favourites',
    GROUPING_HIDDEN: 'hidden'
};

const NUMPROGRAMS_PERPAGE = [12, 24, 48, 96, 0];

let loadedPages = [];

let programOffset = 0;

let lastPage = 0;

let lastLimit = 0;

let namespace = null;

/**
 * Whether the description display has been loaded.
 *
 * If true, this means that programs have been loaded with the description text.
 * Otherwise, switching to the description display mode will require program data to be fetched with the description text.
 *
 * @type {boolean}
 */
let descriptionDisplayLoaded = false;

/**
 * Get filter values from DOM.
 *
 * @param {object} root The root element for the programs view.
 * @return {filters} Set filters.
 */
const getFilterValues = root => {
    const programRegion = root.find(SELECTORS.programView.region);
    return {
        display: programRegion.attr('data-display'),
        grouping: programRegion.attr('data-grouping'),
        sort: programRegion.attr('data-sort'),
        displaycategories: programRegion.attr('data-displaycategories'),
    };
};

// We want the paged content controls below the paged content area.
// and the controls should be ignored while data is loading.
const DEFAULT_PAGED_CONTENT_CONFIG = {
    ignoreControlWhileLoading: true,
    controlPlacementBottom: true,
    persistentLimitKey: 'block_muprogmyoverview_user_paging_preference'
};

/**
 * Get allocated programs from backend.
 *
 * @param {object} filters The filters for this view.
 * @param {int} limit The number of programs to show.
 * @return {promise} Resolved with an array of programs.
 */
const getMyPrograms = (filters, limit) => {
    const params = {
        offset: programOffset,
        limit: limit,
        classification: filters.grouping,
        sort: filters.sort,
    };
    if (filters.display === 'description') {
        params.showdescription = 1;
        descriptionDisplayLoaded = true;
    } else {
        params.showdescription = 0;
    }
    return Repository.getAllocatedProgramsByTimeline(params);
};

/**
 * Search for allocated programs from backend.
 *
 * @param {object} filters The filters for this view.
 * @param {int} limit The number of programs to show.
 * @param {string} searchValue What does the user want to search within their programs.
 * @return {promise} Resolved with an array of programs.
 */
const getSearchMyPrograms = (filters, limit, searchValue) => {
    const params = {
        offset: programOffset,
        limit: limit,
        classification: filters.grouping,
        sort: filters.sort,
        searchvalue: searchValue,
    };
    if (filters.display === 'description') {
        params.showdescription = 1;
        descriptionDisplayLoaded = true;
    } else {
        params.showdescription = 0;
        descriptionDisplayLoaded = false;
    }
    return Repository.getAllocatedProgramsByTimeline(params);
};

/**
 * Get the container element for the favourite icon.
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id number
 * @return {Object} The favourite icon container
 */
const getFavouriteIconContainer = (root, programId) => {
    return root.find(SELECTORS.FAVOURITE_ICON + '[data-program-id="' + programId + '"]');
};

/**
 * Get the paged content container element.
 *
 * @param {Object} root The program overview container
 * @param {Number} index Rendered page index.
 * @return {Object} The rendered paged container.
 */
const getPagedContentContainer = (root, index) => {
    return root.find('[data-region="paged-content-page"][data-page="' + index + '"]');
};

/**
 * Get the program id from a favourite element.
 *
 * @param {Object} root The favourite icon container element.
 * @return {Number} Program id.
 */
const getProgramId = root => {
    return root.attr('data-program-id');
};

/**
 * Hide the favourite icon.
 *
 * @param {Object} root The favourite icon container element.
 * @param {Number} programId Program id number.
 */
const hideFavouriteIcon = (root, programId) => {
    const iconContainer = getFavouriteIconContainer(root, programId);

    const isFavouriteIcon = iconContainer.find(SELECTORS.ICON_IS_FAVOURITE);
    isFavouriteIcon.addClass('hidden');
    Aria.hide(isFavouriteIcon);

    const notFavourteIcon = iconContainer.find(SELECTORS.ICON_NOT_FAVOURITE);
    notFavourteIcon.removeClass('hidden');
    Aria.unhide(notFavourteIcon);
};

/**
 * Show the favourite icon.
 *
 * @param {Object} root The program overview container.
 * @param {Number} programId Program id number.
 */
const showFavouriteIcon = (root, programId) => {
    const iconContainer = getFavouriteIconContainer(root, programId);

    const isFavouriteIcon = iconContainer.find(SELECTORS.ICON_IS_FAVOURITE);
    isFavouriteIcon.removeClass('hidden');
    Aria.unhide(isFavouriteIcon);

    const notFavourteIcon = iconContainer.find(SELECTORS.ICON_NOT_FAVOURITE);
    notFavourteIcon.addClass('hidden');
    Aria.hide(notFavourteIcon);
};

/**
 * Get the action menu item
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id.
 * @return {Object} The add to favourite menu item.
 */
const getAddFavouriteMenuItem = (root, programId) => {
    return root.find('[data-action="add-favourite"][data-program-id="' + programId + '"]');
};

/**
 * Get the action menu item
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id.
 * @return {Object} The remove from favourites menu item.
 */
const getRemoveFavouriteMenuItem = (root, programId) => {
    return root.find('[data-action="remove-favourite"][data-program-id="' + programId + '"]');
};

/**
 * Add program to favourites
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id number
 */
const addToFavourites = (root, programId) => {
    const removeAction = getRemoveFavouriteMenuItem(root, programId);
    const addAction = getAddFavouriteMenuItem(root, programId);

    setProgramFavouriteState(programId, true).then(success => {
        if (success) {
            removeAction.removeClass('hidden');
            addAction.addClass('hidden');
            showFavouriteIcon(root, programId);
        } else {
            Notification.alert('Starring program failed', 'Could not change favourite state');
        }
        return;
    }).catch(Notification.exception);
};

/**
 * Remove program from favourites
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id number
 */
const removeFromFavourites = (root, programId) => {
    const removeAction = getRemoveFavouriteMenuItem(root, programId);
    const addAction = getAddFavouriteMenuItem(root, programId);

    setProgramFavouriteState(programId, false).then(success => {
        if (success) {
            removeAction.addClass('hidden');
            addAction.removeClass('hidden');
            hideFavouriteIcon(root, programId);
        } else {
            Notification.alert('Starring program failed', 'Could not change favourite state');
        }
        return;
    }).catch(Notification.exception);
};

/**
 * Get the action menu item
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id.
 * @return {Object} The hide program menu item.
 */
const getHideProgramMenuItem = (root, programId) => {
    return root.find('[data-action="hide-program"][data-program-id="' + programId + '"]');
};

/**
 * Get the action menu item
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id.
 * @return {Object} The show program menu item.
 */
const getShowProgramMenuItem = (root, programId) => {
    return root.find('[data-action="show-program"][data-program-id="' + programId + '"]');
};

/**
 * Hide program
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id number
 */
const hideProgram = (root, programId) => {
    const hideAction = getHideProgramMenuItem(root, programId);
    const showAction = getShowProgramMenuItem(root, programId);
    const filters = getFilterValues(root);

    setProgramHiddenState(programId, true);

    // Remove the program from this view as it is now hidden and thus not covered by this view anymore.
    // Do only if we are not in "All (including archived)" view mode where really all programs are shown.
    if (filters.grouping !== GROUPINGS.GROUPING_ALLINCLUDINGHIDDEN) {
        hideElement(root, programId);
    }

    hideAction.addClass('hidden');
    showAction.removeClass('hidden');
};

/**
 * Show program
 *
 * @param {Object} root The program overview container
 * @param {Number} programId Program id number
 */
const showProgram = (root, programId) => {
    const hideAction = getHideProgramMenuItem(root, programId);
    const showAction = getShowProgramMenuItem(root, programId);
    const filters = getFilterValues(root);

    setProgramHiddenState(programId, null);

    // Remove the program from this view as it is now shown again and thus not covered by this view anymore.
    // Do only if we are not in "All (including archived)" view mode where really all programs are shown.
    if (filters.grouping !== GROUPINGS.GROUPING_ALLINCLUDINGHIDDEN) {
        hideElement(root, programId);
    }

    hideAction.removeClass('hidden');
    showAction.addClass('hidden');
};

/**
 * Set the programs hidden status and push to repository
 *
 * @param {Number} programId Program id to favourite.
 * @param {Boolean} status new hidden status.
 * @return {Promise} Repository promise.
 */
const setProgramHiddenState = (programId, status) => {

    // If the given status is not hidden, the preference has to be deleted with a null value.
    if (status === false) {
        status = null;
    }

    return setUserPreference(`block_muprogmyoverview_hidden_program_${programId}`, status)
        .catch(Notification.exception);
};

/**
 * Reset the loadedPages dataset to take into account the hidden element
 *
 * @param {Object} root The program overview container
 * @param {Number} id The program id number
 */
const hideElement = (root, id) => {
    const pagingBar = root.find('[data-region="paging-bar"]');
    const jumpto = parseInt(pagingBar.attr('data-active-page-number'));

    // Get a reduced dataset for the current page.
    const programList = loadedPages[jumpto];
    let reducedProgram = programList.programs.reduce((accumulator, current) => {
        if (+id !== +current.id) {
            accumulator.push(current);
        }
        return accumulator;
    }, []);

    // Get the next page's data if loaded and pop the first element from it.
    if (typeof (loadedPages[jumpto + 1]) !== 'undefined') {
        const newElement = loadedPages[jumpto + 1].programs.slice(0, 1);

        // Adjust the dataset for the reset of the pages that are loaded.
        loadedPages.forEach((programList, index) => {
            if (index > jumpto) {
                let popElement = [];
                if (typeof (loadedPages[index + 1]) !== 'undefined') {
                    popElement = loadedPages[index + 1].programs.slice(0, 1);
                }
                loadedPages[index].programs = [...loadedPages[index].programs.slice(1), ...popElement];
            }
        });

        reducedProgram = [...reducedProgram, ...newElement];
    }

    // Check if the next page is the last page and if it still has data associated to it.
    if (lastPage === jumpto + 1 && loadedPages[jumpto + 1].programs.length === 0) {
        const pagedContentContainer = root.find('[data-region="paged-content-container"]');
        PagedContentFactory.resetLastPageNumber($(pagedContentContainer).attr('id'), jumpto);
    }

    loadedPages[jumpto].programs = reducedProgram;

    // Reduce the program offset.
    programOffset--;

    // Render the paged content for the current.
    const pagedContentPage = getPagedContentContainer(root, jumpto);
    renderPrograms(root, loadedPages[jumpto]).then((html, js) => {
        return Templates.replaceNodeContents(pagedContentPage, html, js);
    }).catch(Notification.exception);

    // Delete subsequent pages in order to trigger the callback.
    loadedPages.forEach((programList, index) => {
        if (index > jumpto) {
            const page = getPagedContentContainer(root, index);
            page.remove();
        }
    });
};

/**
 * Set the programs favourite status and push to repository
 *
 * @param {Number} programId Program id to favourite.
 * @param {boolean} status new favourite status.
 * @return {Promise} Repository promise.
 */
const setProgramFavouriteState = (programId, status) => {

    return Repository.setFavouriteProgram({
        'id': programId,
        'favourite': status
    }).then(result => {
        if (result.warnings.length === 0) {
            loadedPages.forEach(programList => {
                programList.programs.forEach((program, index) => {
                    if (program.id == programId) {
                        programList.programs[index].isfavourite = status;
                    }
                });
            });
            return true;
        } else {
            return false;
        }
    }).catch(Notification.exception);
};

/**
 * Given there are no programs to render provide the rendered template.
 *
 * @param {object} root The root element for the programs view.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const noProgramsRender = root => {
    const noprogramsimg = root.find(SELECTORS.programView.region).attr('data-noprogramsimg');
    return Templates.render(TEMPLATES.NOPROGRAMS, {
        noprogramsimg: noprogramsimg
    });
};

/**
 * Render the dashboard programs.
 *
 * @param {object} root The root element for the programs view.
 * @param {array} programsData containing array of returned programs.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const renderPrograms = (root, programsData) => {

    const filters = getFilterValues(root);

    let currentTemplate = '';
    if (filters.display === 'card') {
        currentTemplate = TEMPLATES.PROGRAMS_CARDS;
    } else if (filters.display === 'list') {
        currentTemplate = TEMPLATES.PROGRAMS_LIST;
    } else {
        currentTemplate = TEMPLATES.PROGRAMS_DESCRIPTION;
    }

    if (!programsData) {
        return noProgramsRender(root);
    } else {
        // Sometimes we get weird objects coming after a failed search, cast to ensure typing functions.
        if (Array.isArray(programsData.programs) === false) {
            programsData.programs = Object.values(programsData.programs);
        }
        // Whether the program category should be displayed in the program item.
        programsData.programs = programsData.programs.map(program => {
            program.showprogramcategory = filters.displaycategories === 'on';
            return program;
        });
        if (programsData.programs.length) {
            return Templates.render(currentTemplate, {
                programs: programsData.programs,
            });
        } else {
            return noProgramsRender(root);
        }
    }
};

/**
 * Return the callback to be passed to the subscribe event
 *
 * @param {object} root The root element for the programs view
 * @return {function} Partially applied function that'll execute when passed a limit
 */
const setLimit = root => {
    // @param {Number} limit The paged limit that is passed through the event.
    return limit => root.find(SELECTORS.programView.region).attr('data-paging', limit);
};

/**
 * Intialise the paged list and cards views on page load.
 * Returns an array of paged contents that we would like to handle here
 *
 * @param {object} root The root element for the programs view
 * @param {string} namespace The namespace for all the events attached
 */
const registerPagedEventHandlers = (root, namespace) => {
    const event = namespace + PagedContentEvents.SET_ITEMS_PER_PAGE_LIMIT;
    PubSub.subscribe(event, setLimit(root));
};

/**
 * Figure out how many items are going to be allowed to be rendered in the block.
 *
 * @param  {Number} pagingLimit How many programs to display
 * @param  {Object} root The program overview container
 * @return {Number[]} How many programs will be rendered
 */
const itemsPerPageFunc = (pagingLimit, root) => {
    let itemsPerPage = NUMPROGRAMS_PERPAGE.map(value => {
        let active = false;
        if (value === pagingLimit) {
            active = true;
        }

        return {
            value: value,
            active: active
        };
    });

    // Filter out all pagination options which are too large for the amount of programs user is allocated in.
    const totalProgramCount = parseInt(root.find(SELECTORS.programView.region).attr('data-totalprogramcount'), 10);
    return itemsPerPage.filter(pagingOption => {
        if (pagingOption.value === 0 && totalProgramCount > 100) {
            // To minimise performance issues, do not show the "All" option if the user is allocated in more than 100 programs.
            return false;
        }
        return pagingOption.value < totalProgramCount;
    });
};

/**
 * Mutates and controls the loadedPages array and handles the bootstrapping.
 *
 * @param {Array|Object} programsData Array of all of the programs to start building the page from
 * @param {Number} currentPage What page are we currently on?
 * @param {Object} pageData Any current page information
 * @param {Object} actions Paged content helper
 * @param {null|boolean} activeSearch Are we currently actively searching and building up search results?
 */
const pageBuilder = (programsData, currentPage, pageData, actions, activeSearch = null) => {
    // If the programData comes in an object then get the value otherwise it is a pure array.
    let programs = programsData.programs ? programsData.programs : programsData;
    let nextPageStart = 0;
    let pagePrograms = [];

    // If current page's data is loaded make sure we max it to page limit.
    if (typeof (loadedPages[currentPage]) !== 'undefined') {
        pagePrograms = loadedPages[currentPage].programs;
        const currentPageLength = pagePrograms.length;
        if (currentPageLength < pageData.limit) {
            nextPageStart = pageData.limit - currentPageLength;
            pagePrograms = {...loadedPages[currentPage].programs, ...programs.slice(0, nextPageStart)};
        }
    } else {
        // When the page limit is zero, there is only one page of programs, no start for next page.
        nextPageStart = pageData.limit || false;
        pagePrograms = (pageData.limit > 0) ? programs.slice(0, pageData.limit) : programs;
    }

    // Finished setting up the current page.
    loadedPages[currentPage] = {
        programs: pagePrograms
    };

    // Set up the next page (if there is more than one page).
    const remainingPrograms = nextPageStart !== false ? programs.slice(nextPageStart, programs.length) : [];
    if (remainingPrograms.length) {
        loadedPages[currentPage + 1] = {
            programs: remainingPrograms
        };
    }

    // Set the last page to either the current or next page.
    if (loadedPages[currentPage].programs.length < pageData.limit || !remainingPrograms.length) {
        lastPage = currentPage;
        if (activeSearch === null) {
            actions.allItemsLoaded(currentPage);
        }
    } else if (typeof (loadedPages[currentPage + 1]) !== 'undefined'
        && loadedPages[currentPage + 1].programs.length < pageData.limit) {
        lastPage = currentPage + 1;
    }

    programOffset = programsData.nextoffset;
};

/**
 * In cases when switching between regular rendering and search rendering we need to reset some variables.
 */
const resetGlobals = () => {
    programOffset = 0;
    loadedPages = [];
    lastPage = 0;
    lastLimit = 0;
};

/**
 * The default functionality of fetching paginated programs without special handling.
 *
 * @return {function(Object, Object, Object, Object, Object, Promise, Number): void}
 */
const standardFunctionalityCurry = () => {
    resetGlobals();
    return (filters, currentPage, pageData, actions, root, promises, limit) => {
        const pagePromise = getMyPrograms(
            filters,
            limit
        ).then(programsData => {
            pageBuilder(programsData, currentPage, pageData, actions);
            return renderPrograms(root, loadedPages[currentPage]);
        }).catch(Notification.exception);

        promises.push(pagePromise);
    };
};

/**
 * Initialize the searching functionality so we can call it when required.
 *
 * @return {function(Object, Number, Object, Object, Object, Promise, Number, String): void}
 */
const searchFunctionalityCurry = () => {
    resetGlobals();
    return (filters, currentPage, pageData, actions, root, promises, limit, inputValue) => {
        const searchingPromise = getSearchMyPrograms(
            filters,
            limit,
            inputValue
        ).then(programsData => {
            pageBuilder(programsData, currentPage, pageData, actions);
            return renderPrograms(root, loadedPages[currentPage]);
        }).catch(Notification.exception);

        promises.push(searchingPromise);
    };
};

/**
 * Initialise the programs list and cards views on page load.
 *
 * @param {object} root The root element for the programs view.
 * @param {function} promiseFunction How do we fetch the programs and what do we do with them?
 * @param {null | string} inputValue What to search for
 */
const initializePagedContent = (root, promiseFunction, inputValue = null) => {
    const pagingLimit = parseInt(root.find(SELECTORS.programView.region).attr('data-paging'), 10);
    let itemsPerPage = itemsPerPageFunc(pagingLimit, root);

    const config = {...{}, ...DEFAULT_PAGED_CONTENT_CONFIG};
    config.eventNamespace = namespace;

    const pagedContentPromise = PagedContentFactory.createWithLimit(
        itemsPerPage,
        (pagesData, actions) => {
            let promises = [];
            pagesData.forEach(pageData => {
                const currentPage = pageData.pageNumber;
                let limit = (pageData.limit > 0) ? pageData.limit : 0;

                // Reset local variables if limits have changed.
                if (+lastLimit !== +limit) {
                    loadedPages = [];
                    programOffset = 0;
                    lastPage = 0;
                }

                if (lastPage === currentPage) {
                    // If we are on the last page and have it's data then load it from cache.
                    actions.allItemsLoaded(lastPage);
                    promises.push(renderPrograms(root, loadedPages[currentPage]));
                    return;
                }

                lastLimit = limit;

                // Get 2 pages worth of data as we will need it for the hidden functionality.
                if (typeof (loadedPages[currentPage + 1]) === 'undefined') {
                    if (typeof (loadedPages[currentPage]) === 'undefined') {
                        limit *= 2;
                    }
                }

                // Get the current applied filters.
                const filters = getFilterValues(root);

                // Call the curried function that'll handle the program promise and any manipulation of it.
                promiseFunction(filters, currentPage, pageData, actions, root, promises, limit, inputValue);
            });
            return promises;
        },
        config
    );

    pagedContentPromise.then((html, js) => {
        registerPagedEventHandlers(root, namespace);
        return Templates.replaceNodeContents(root.find(SELECTORS.programView.region), html, js);
    }).catch(Notification.exception);
};

/**
 * Listen to, and handle events for the muprogmyoverview block.
 *
 * @param {Object} root The muprogmyoverview block container element.
 * @param {HTMLElement} page The whole HTMLElement for our block.
 */
const registerEventListeners = (root, page) => {

    CustomEvents.define(root, [
        CustomEvents.events.activate
    ]);

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_ADD_FAVOURITE, (e, data) => {
        const favourite = $(e.target).closest(SELECTORS.ACTION_ADD_FAVOURITE);
        const programId = getProgramId(favourite);
        addToFavourites(root, programId);
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_REMOVE_FAVOURITE, (e, data) => {
        const favourite = $(e.target).closest(SELECTORS.ACTION_REMOVE_FAVOURITE);
        const programId = getProgramId(favourite);
        removeFromFavourites(root, programId);
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.FAVOURITE_ICON, (e, data) => {
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_HIDE_PROGRAM, (e, data) => {
        const target = $(e.target).closest(SELECTORS.ACTION_HIDE_PROGRAM);
        const programId = getProgramId(target);
        hideProgram(root, programId);
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_SHOW_PROGRAM, (e, data) => {
        const target = $(e.target).closest(SELECTORS.ACTION_SHOW_PROGRAM);
        const programId = getProgramId(target);
        showProgram(root, programId);
        data.originalEvent.preventDefault();
    });

    // Searching functionality event handlers.
    const input = page.querySelector(SELECTORS.region.searchInput);
    const clearIcon = page.querySelector(SELECTORS.region.clearIcon);

    clearIcon.addEventListener('click', () => {
        input.value = '';
        input.focus();
        clearSearch(clearIcon, root);
    });

    input.addEventListener('input', debounce(() => {
        if (input.value === '') {
            clearSearch(clearIcon, root);
        } else {
            activeSearch(clearIcon);
            initializePagedContent(root, searchFunctionalityCurry(), input.value.trim());
        }
    }, 1000));
};

/**
 * Reset the search icon and trigger the init for the block.
 *
 * @param {HTMLElement} clearIcon Our closing icon to manipulate.
 * @param {Object} root The muprogmyoverview block container element.
 */
export const clearSearch = (clearIcon, root) => {
    clearIcon.classList.add('d-none');
    init(root);
};

/**
 * Change the searching icon to its' active state.
 *
 * @param {HTMLElement} clearIcon Our closing icon to manipulate.
 */
const activeSearch = (clearIcon) => {
    clearIcon.classList.remove('d-none');
};

/**
 * Intialise the programs list and cards views on page load.
 *
 * @param {object} root The root element for the programs view.
 */
export const init = root => {
    root = $(root);
    loadedPages = [];
    lastPage = 0;
    programOffset = 0;

    if (!root.attr('data-init')) {
        const page = document.querySelector(SELECTORS.region.selectBlock);
        registerEventListeners(root, page);
        namespace = "block_muprogmyoverview_" + root.attr('id') + "_" + Math.random();
        root.attr('data-init', true);
    }

    initializePagedContent(root, standardFunctionalityCurry());
};

/**
 * Reset the programs views to their original
 * state on first page load.programOffset
 *
 * This is called when configuration has changed for the event lists
 * to cause them to reload their data.
 *
 * @param {Object} root The root element for the timeline view.
 */
export const reset = root => {
    if (loadedPages.length > 0) {
        const filters = getFilterValues(root);
        // If the display mode is changed to 'description' but the description display has not been loaded yet,
        // we need to re-fetch the programs to include the program description text.
        if (filters.display === 'description' && !descriptionDisplayLoaded) {
            const page = document.querySelector(SELECTORS.region.selectBlock);
            const input = page.querySelector(SELECTORS.region.searchInput);
            if (input.value !== '') {
                initializePagedContent(root, searchFunctionalityCurry(), input.value.trim());
            } else {
                initializePagedContent(root, standardFunctionalityCurry());
            }
        } else {
            loadedPages.forEach((programList, index) => {
                let pagedContentPage = getPagedContentContainer(root, index);
                renderPrograms(root, programList).then((html, js) => {
                    return Templates.replaceNodeContents(pagedContentPage, html, js);
                }).catch(Notification.exception);
            });
        }
    } else {
        init(root);
    }
};
