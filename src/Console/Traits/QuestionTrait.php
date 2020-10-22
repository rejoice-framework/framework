<?php

namespace Rejoice\Console\Traits;

use Symfony\Component\Console\Question\Question;

/**
 * Console questions related methods.
 * 
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
trait QuestionTrait
{
    /**
     * Ask user for a response.
     *
     * @param  string $question The question to ask
     * @param  mixed  $default  The default value of the response
     * @return void
     */
    public function ask($question, $default = null)
    {
        $helper = $this->getHelper('question');
        $args = \func_get_args();
        $quest = new Question(...$args);

        return $helper->ask($this->getInput(), $this->getOutput(), $quest);
    }

    /**
     * Ask user for confirmation.
     *
     * @param  string|string[] $question            The question to ask
     * @param  mixed           $defaultResponse
     * @param  array           $validResponses
     * @param  array           $invalidResponses
     * @return bool
     */
    public function confirm(
        $questions,
        $defaultResponse = 'no',
        array $validResponses = ['y', 'yes'],
        array $invalidResponses = ['n', 'no']
    ) {
        if (!is_array($questions)) {
            $questions = [$questions];
        }

        $last = count($questions) - 1;

        foreach ($questions as $key => $quest) {
            if ($key === $last) {
                break;
            }

            $this->writeln($quest);
        }

        $hasAccepted = null;
        $hasDeclined = null;

        do {
            if (null !== $hasAccepted && null !== $hasDeclined) {
                $this->error(
                    'Response must be '.implode(', ', $validResponses).' or '.implode(', ', $invalidResponses)
                );
            }

            $response = $this->ask($questions[$last]." [$defaultResponse]: ", $defaultResponse);
            $response = strtolower($response);

            if (!($hasAccepted = in_array($response, $validResponses))) {
                $hasDeclined = in_array($response, $invalidResponses);
            }
        } while (!$hasAccepted && !$hasDeclined);

        return (bool) $hasAccepted;
    }
}
