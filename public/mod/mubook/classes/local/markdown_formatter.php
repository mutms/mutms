<?php
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

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
// phpcs:disable moodle.Commenting.VariableComment.Missing
// phpcs:disable moodle.Commenting.MissingDocblock.Function
// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch

namespace mod_mubook\local;

use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Query;
use League\CommonMark\Event\DocumentParsedEvent;

/**
 * Markdown helper.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class markdown_formatter {
    /** @var int remove all html */
    public const HTML_STRIP = 1;
    /** @var int escape all html */
    public const HTML_ESCAPE = 2;
    /** @var int keep most of the html - html is sanitised after conversion */
    public const HTML_ALLOW = 3;

    /**
     * Returns options for handling of HTML embedded in Markdown.
     *
     * @return array
     */
    public static function get_html_options(): array {
        return [
            self::HTML_STRIP => get_string('markdown_html_strip', 'mod_mubook'),
            self::HTML_ESCAPE => get_string('markdown_html_escape', 'mod_mubook'),
            self::HTML_ALLOW => get_string('markdown_html_allow', 'mod_mubook'),
        ];
    }

    /**
     * Convert Markdown to HTML.
     *
     * @param string $markdown
     * @param array $options
     * @return string
     */
    public static function convert_to_html(string $markdown, array $options = []): string {
        require_once(__DIR__ . '/../../vendor/autoload.php');

        $firstheading = $options['firstheading'] ?? 1;
        $firstheading = min(6, max(1, $firstheading));

        $filebase = $options['filebase'] ?? null;

        $headingoffset = $options['headingoffset'] ?? 0;

        $config = [
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'max_delimiters_per_line' => 100,
        ];

        if (isset($options['html']) && $options['html'] == self::HTML_ESCAPE) {
            $config['html_input'] = 'escape';
        } else if (isset($options['html']) && $options['html'] == self::HTML_ALLOW) {
            $config['html_input'] = 'allow';
        } else {
            $config['html_input'] = 'strip';
        }

        $config['alert'] = [
            'class_name' => 'mubook-alert',
            'labels' => [
                'note' => get_string('markdown_alert_note', 'mod_mubook'),
                'tip' => get_string('markdown_alert_tip', 'mod_mubook'),
                'important' => get_string('markdown_alert_important', 'mod_mubook'),
                'warning' => get_string('markdown_alert_warning', 'mod_mubook'),
                'caution' => get_string('markdown_alert_caution', 'mod_mubook'),
            ],
            'icons' => [
                'active' => true,
                'names' => [
                    'note' => 'fa-solid fa-circle-info me-1',
                    'tip' => 'fa-regular fa-lightbulb me-1',
                    'important' => 'fa-solid fa-book-open-reader me-1',
                    'warning' => 'fa-solid fa-triangle-exclamation me-1',
                    'caution' => 'fa-solid fa-circle-exclamation me-1',
                ],
            ],
        ];

        $config['task'] = [
            'labels' => [
                'completed' => get_string('markdown_task_completed', 'mod_mubook'),
                'notcompleted' => get_string('markdown_task_notcompleted', 'mod_mubook'),
            ],
        ];

        // Do not include GithubFlavoredMarkdownExtension here,
        // we do not want autolinking (done via filters) and upstream tasks (not compatible with HTMLPurifier).
        $environment = new Environment($config);
        $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
        $environment->addExtension(new \League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension());
        $environment->addExtension(new \League\CommonMark\Extension\Strikethrough\StrikethroughExtension());
        $environment->addExtension(new \League\CommonMark\Extension\Table\TableExtension());
        $environment->addExtension(new \PomoDocs\CommonMark\Alert\AlertExtension());
        $environment->addExtension(new \MuTMS\CommonMark\Extra\ExtraExtension());

        // Normalise headings, optionally shift visual heading level with CSS.
        $diff = null;
        $environment->addEventListener(
            DocumentParsedEvent::class,
            function (DocumentParsedEvent $e) use ($firstheading, &$diff, $headingoffset): void {
                $document = $e->getDocument();
                $query = (new Query())->where(function (Node $node): bool {
                    return $node instanceof Heading;
                });
                /** @var Heading $node */
                foreach ($query->findAll($document) as $node) {
                    $level = $node->getLevel();
                    if ($diff === null) {
                        $diff = $firstheading - $level;
                    }
                    $node->setLevel(min(6, max($firstheading, $level + $diff)));

                    if ($headingoffset) {
                        $newlevel = min(6, max(1, $node->getLevel() + $headingoffset));
                        $attributes = $node->data->get('attributes') ?? [];
                        $attributes['class'] = 'h' . $newlevel;
                        $node->data->set('attributes', $attributes);
                    }
                }
            },
            9999999
        );

        // Fix relative image urls.
        $imagerender = new class ($filebase) implements NodeRendererInterface {
            public function __construct(protected ?string $filebase) {
            }

            public function render(Node $node, ChildNodeRendererInterface $childRenderer) {
                /** @var Image $node */
                Image::assertInstanceOf($node);
                $url = $node->getUrl();
                $title = $node->getTitle();
                if ($this->filebase !== null) {
                    if (str_starts_with($url, '@@PLUGINFILE@@/')) {
                        $url = $this->filebase . substr($url, strlen('@@PLUGINFILE@@/'));
                    } else if (str_starts_with($url, './')) {
                        $url = $this->filebase . substr($url, 2);
                    } else if (!str_starts_with($url, '/') && !preg_match('/^[a-zA-Z]+:/', $url)) {
                        $url = $this->filebase . $url;
                    }
                }
                return \html_writer::img($url, $title, ['class' => 'img-fluid']);
            }
        };
        $environment->addRenderer(Image::class, $imagerender);

        // Fix relative link urls.
        $linkrender = new class ($filebase) implements NodeRendererInterface {
            public function __construct(protected ?string $filebase) {
            }

            public function render(Node $node, ChildNodeRendererInterface $childRenderer) {
                /** @var Link $node */
                Link::assertInstanceOf($node);
                $url = $node->getUrl();
                $text = $childRenderer->renderNodes($node->children());
                $title = $node->getTitle();
                if ($this->filebase !== null && trim($url ?? '') !== '' && !str_starts_with($url, '#')) {
                    if (str_starts_with($url, '@@PLUGINFILE@@/')) {
                        $url = $this->filebase . substr($url, strlen('@@PLUGINFILE@@/'));
                    } else if (str_starts_with($url, './')) {
                        $url = $this->filebase . substr($url, 2);
                    } else if (!str_starts_with($url, '/') && !preg_match('/^[a-zA-Z]+:/', $url)) {
                        $url = $this->filebase . $url;
                    }
                }
                $attributes = [];
                if (isset($title) && $title !== '') {
                    $attributes['title'] = $title;
                }
                return \html_writer::link($url, $text, $attributes);
            }
        };
        $environment->addRenderer(Link::class, $linkrender);

        $converter = new MarkdownConverter($environment);
        $html = $converter->convert($markdown);

        // We must sanitise HTML here.
        // Unfortunately there is a known problem with link anchors - the name attributes from 'a' tags are removed.
        return clean_text($html, FORMAT_HTML);
    }
}
