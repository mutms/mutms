# Interactive book for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-mod_mubook/actions/workflows/moodle-ci.yml/badge.svg)

Interactive book module is the successor to original Book module for Moodle. It is part of [MuTMS](https://github.com/mutms/) suite of plugins.

## New features and improvements

1. Modern look and feel
2. Full-page table of contents
3. Page showing all chapters
4. Redesigned chapter navigation
5. Full support for Markdown format
6. Support for new chapter content types including interactive elements
7. Print sub-plugin was replaced with optimised printing support on regular book pages 
8. Hidden chapters were replaced with hidden chapter content
9. Editing of unsafe HTML content is controlled by a separate capability
10. It is safe to allow students to edit chapters and content  

## Future ideas

* Markdown preview
* Chapter content visibility based on group membership
* Content snapshots for tracking of changes
* Optional user experience tracking
* Export and import
* Integration of react-markdown editor
* Full text search
* New block with table of contents, progress indicators and bookmarks
* Reusable content libraries

_Note that development of new features will depend on availability of funding._

## Markdown support

Interactive book supports a subset of [GitHub Flavored Markdown](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax). 

Additional features:

* content files are accessible via relative links or @@PLUGINFILE@@ prefix

## Installation from plugins database

1. Install [Additional tools library for MuTMS plugins](https://moodle.org/plugins/view.php?id=3560) plugin
2. Install [interactive book](https://moodle.org/plugins/view.php?id=3822) plugin

## Installation via git

```bash
cd moodle
git clone -b MOODLE_405_STABLE https://github.com/mutms/moodle-mod_mubook.git mod/mubook
git clone -b MOODLE_405_STABLE https://github.com/mutms/moodle-tool_mulib.git admin/tool/mulib
```

_Note that the preview version is not compatible with Moodle 4.5 and earlier._

## Credits

Original Book module was developed for Technical University of Liberec (Czech Republic).

The impulse to create new Interactive book module came from the participants of
[Moodle Moot DACH 2025](https://moodlemootdach.org/mod/forum/discuss.php?d=7076).

The user interface of Interactive book module was inspired by [The Modern JavaScript Tutorial](https://javascript.info/) design.

Developers (including legacy Book module):

* Petr Skoda - majority the coding and design
* Mojmir Volf, Eloy Lafuente, Antonio Vicent, Moodle HQ and others
