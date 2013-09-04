<?php
namespace UriTemplate\Test;

class UriTemplateTest extends \PHPUnit_Framework_TestCase
{
    public function testGetVariables()
    {
        $this->assertSame(range('a', 'e'), \UriTemplate\UriTemplate::getVariables($template = '{a,b}/{#c}/x{d*,e}'));
    }

    /**
     * @dataProvider getTestData
     */
    public function testTemplates($processor, $template, $expected)
    {
        try {
            $processor->setTemplate($template);
            $actual = $processor->process();
        } catch (\UriTemplate\UriTemplateException $e) {
            $actual = false;
            $errors = $e->getErrors();
            $this->assertSame('array', gettype($errors));
            $this->assertGreaterThan(0, count($errors));
        }
        $this->assertTrue(in_array($actual, $expected));
    }

    public function getTestData()
    {
        $data = array();
        foreach (glob(dirname(dirname(__DIR__)).'/*.json') as $file) {
            $testdata = json_decode(file_get_contents($file));
            if ($testdata === null) {
                switch (json_last_error()) {
                    case JSON_ERROR_UTF8:
                        $this->fail('Malformed UTF-8 characters, possibly incorrectly encoded');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $this->fail('Syntax error');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $this->fail('Control character error, possibly incorrectly encoded');
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $this->fail('Invalid or malformed JSON');
                        break;
                    case JSON_ERROR_DEPTH:
                        $this->fail('The maximum stack depth has been exceeded');
                        break;
                    case JSON_ERROR_NONE:
                        $this->fail('No error has occurred');
                        break;
                    default:
                        $this->fail('Unknown error');
                        break;
                }
                return;
            }
            foreach ($testdata as $testsuite) {            
                $processor = new \UriTemplate\Processor;
                $processor->setData(get_object_vars($testsuite->variables));
                foreach ($testsuite->testcases as $testcase) {
                    $template = $testcase[0];
                    $expected = (array)$testcase[1];
                    $data[] = array($processor, $template, $expected);
                }
            }
        }
        return $data;
    }

}
