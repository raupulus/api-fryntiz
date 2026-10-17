<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use App\Helpers\TextFormatParseHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La sangría del código llega a la web (auditoría del editor del 2026-10-14).
 *
 * `arrayToHtml()` quita los tabuladores y junta en uno los espacios de toda la
 * página, y el HTML junta los que queden: un bloque de código salía en la web
 * con todas las líneas pegadas a la izquierda.
 */
class CodeIndentationTest extends TestCase
{
    #[Test]
    public function a_code_block_keeps_its_indentation_and_tabs(): void
    {
        $html = $this->code("function hola() {\n    if (true) {\n\treturn [1,  2];\n    }\n}");

        $this->assertSame(
            'function hola() {<br>&nbsp;&nbsp;&nbsp;&nbsp;if (true) {<br>&nbsp;&nbsp;&nbsp;&nbsp;return [1,&nbsp;&nbsp;2];<br>&nbsp;&nbsp;&nbsp;&nbsp;}<br>}',
            $html,
        );
    }

    #[Test]
    public function a_single_space_between_words_stays_so_the_line_can_wrap(): void
    {
        $this->assertSame('echo &quot;a b&quot; | grep a', $this->code('echo "a b" | grep a'));
    }

    #[Test]
    public function code_is_still_escaped(): void
    {
        $this->assertSame('&lt;div&gt;<br>&nbsp;&nbsp;&lt;p&gt;x&lt;/p&gt;<br>&lt;/div&gt;', $this->code("<div>\n  <p>x</p>\n</div>"));
    }

    #[Test]
    public function inside_a_pre_of_a_raw_block_the_indentation_stays_and_outside_nothing_changes(): void
    {
        $html = TextFormatParseHelper::arrayToHtml([['id' => 'r1', 'type' => 'raw', 'data' => [
            'html' => "<p>Monta  así:</p>\n<pre><code class=\"language-bash\">sudo mount \\\n    -t ext4</code></pre>",
        ]]]);

        $this->assertStringContainsString('<pre><code class="language-bash">sudo mount \\<br>&nbsp;&nbsp;&nbsp;&nbsp;-t ext4</code></pre>', $html);
        $this->assertStringContainsString('<p>Monta así:</p>', $html, 'Fuera del <pre>, como antes.');
    }

    private function code(string $code): string
    {
        $html = TextFormatParseHelper::arrayToHtml([['id' => 'c1', 'type' => 'code', 'data' => ['code' => $code, 'language' => 'php']]]);

        preg_match('/<code class="r-codeblock"[^>]*>(.*?)<\/code>/s', $html, $match);

        return $match[1] ?? '';
    }
}
