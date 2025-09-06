<?php
// ai_utils.php
// Enhanced AI utilities with improved quiz generation

class AIUtils {
    /** Basic English stopwords. Adjust as needed. */
    public static function stopwords(): array {
        static $stop = null;
        if ($stop !== null) return $stop;
        $list = 'a,about,above,after,again,against,all,am,an,and,any,are,arent,as,at,be,because,been,
        before,being,below,between,both,but,by,cant,cannot,could,couldnt,did,didnt,do,does,doesnt,doing,
        dont,down,during,each,few,for,from,further,had,hadnt,has,hasnt,have,havent,having,he,hed,hell,hes,
        her,here,heres,hers,herself,him,himself,his,how,hows,i,id,ill,im,ive,if,in,into,is,isnt,it,its,
        itself,lets,me,more,most,mustnt,my,myself,no,nor,not,of,off,on,once,only,or,other,ought,our,ours,
        ourselves,out,over,own,same,shant,she,shed,shell,shes,should,shouldnt,so,some,such,than,that,thats,
        the,their,theirs,them,themselves,then,there,theres,these,they,theyd,theyll,theyre,theyve,this,those,
        through,to,too,under,until,up,very,was,wasnt,we,wed,well,we,re,were,werent,what,whats,when,whens,
        where,wheres,which,while,who,whom,whys,with,wont,would,wouldnt,you,youd,youll,youre,youve,your,yours,
        yourself,yourselves';
        $stop = array_fill_keys(array_map('trim', explode(',', preg_replace('/\s+/', '', $list))), true);
        return $stop;
    }

    /** Sentence splitter (simple, fast). */
    public static function sentences(string $text): array {
        $text = preg_replace('/\s+/', ' ', trim($text));
        // Split on ., ?, ! followed by space/quote or end
        $parts = preg_split('/(?<=[\.\?\!])\s+(?=[A-Z0-9"\'])/u', $text);
        $parts = array_values(array_filter(array_map('trim', $parts)));
        return $parts ?: ($text ? [$text] : []);
    }

    /** Tokenize to lowercase words (keep alphanumerics). */
    public static function tokens(string $text): array {
        $text = mb_strtolower($text, 'UTF-8');
        $words = preg_split('/[^a-z0-9]+/u', $text);
        return array_values(array_filter($words, fn($w) => $w !== ''));
    }

    /** Word frequencies excluding stopwords. */
    public static function wordFreq(string $text): array {
        $stop = self::stopwords();
        $freq = [];
        foreach (self::tokens($text) as $w) {
            if (isset($stop[$w])) continue;
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
        // normalize 0..1
        if ($freq) {
            $max = max($freq);
            foreach ($freq as $k => $v) $freq[$k] = $v / $max;
        }
        return $freq;
    }

    /** Score sentences by sum of word scores / log(length). */
    public static function scoreSentences(array $sentences, array $freq): array {
        $scores = [];
        foreach ($sentences as $i => $s) {
            $words = self::tokens($s);
            $sum = 0.0;
            foreach ($words as $w) $sum += $freq[$w] ?? 0.0;
            $len = max(1, count($words));
            $scores[$i] = $sum / log(3 + $len);
        }
        return $scores;
    }

    /** Normalize for de-dup (lowercase alnum only). */
    private static function norm(string $s): string {
        $s = mb_strtolower($s, 'UTF-8');
        return preg_replace('/[^a-z0-9]+/u', '', $s);
    }

    /** Dedupe an array of strings using norm(). */
    private static function dedupeByNorm(array $items): array {
        $seen = []; $out = [];
        foreach ($items as $it) {
            $n = self::norm($it);
            if ($n === '' || isset($seen[$n])) continue;
            $seen[$n] = true;
            $out[] = $it;
        }
        return $out;
    }

    /** Pick the sentence that best mentions the concept. */
    private static function bestContextSentence(array $sentences, string $concept): ?string {
        $c = mb_strtolower($concept, 'UTF-8');
        $best = null; $bestScore = 0.0;
        foreach ($sentences as $s) {
            $sl = mb_strtolower($s, 'UTF-8');
            if (strpos($sl, $c) === false) continue;
            // Prefer concise but informative sentences
            $len = max(20, mb_strlen($s));
            $score = 1.0 / log(10 + $len); // shorter a bit better
            // Prefer if the concept appears earlier
            $pos = mb_strpos($sl, $c);
            if ($pos !== false) $score += 0.5 / (1 + $pos);
            if ($score > $bestScore) { $bestScore = $score; $best = $s; }
        }
        return $best;
    }

    /** Generate concise explanations for quiz questions */
    private static function generateConciseExplanation(string $concept, string $context, string $type): string {
        // Extract key information from context and create a focused explanation
        $conceptLower = mb_strtolower($concept);
        $contextLower = mb_strtolower($context);
        
        // Find the part of the sentence that contains the concept
        $conceptPos = mb_strpos($contextLower, $conceptLower);
        if ($conceptPos === false) {
            return self::clip($context, 150);
        }
        
        // Extract a focused explanation around the concept
        $start = max(0, $conceptPos - 50);
        $length = min(200, mb_strlen($context) - $start);
        $extract = mb_substr($context, $start, $length);
        
        // Clean up the extract to make it a complete thought
        if ($start > 0) {
            $firstSpace = mb_strpos($extract, ' ');
            if ($firstSpace !== false) {
                $extract = mb_substr($extract, $firstSpace + 1);
            }
        }
        
        // Ensure it ends properly
        $lastPeriod = mb_strrpos($extract, '.');
        if ($lastPeriod !== false && $lastPeriod > 50) {
            $extract = mb_substr($extract, 0, $lastPeriod + 1);
        }
        
        // Create type-specific explanations
        switch ($type) {
            case 'definition':
                return "The episode defines \"" . $concept . "\" as: " . self::clip($extract, 120);
            case 'purpose':
                return "According to the episode, \"" . $concept . "\" serves this purpose: " . self::clip($extract, 120);
            case 'application':
                return "The episode provides this example of \"" . $concept . "\": " . self::clip($extract, 120);
            case 'comparison':
                return "The episode compares \"" . $concept . "\" in this way: " . self::clip($extract, 120);
            case 'cause_effect':
                return "The episode explains this relationship involving \"" . $concept . "\": " . self::clip($extract, 120);
            case 'sequence':
                return "The episode describes this sequence involving \"" . $concept . "\": " . self::clip($extract, 120);
            case 'evaluation':
                return "The episode evaluates \"" . $concept . "\" as follows: " . self::clip($extract, 120);
            case 'cloze':
                return "The episode states: " . self::clip($extract, 120);
            case 'factual':
                return "The episode states: " . self::clip($extract, 120);
            case 'inferential':
                return "Based on the episode's discussion: " . self::clip($extract, 120);
            case 'analytical':
                return "The episode explains this relationship: " . self::clip($extract, 120);
            default:
                return self::clip($extract, 150);
        }
    }

    /** Build a cloze stem from a sentence with the concept blanked, plus a lead-in. */
    private static function makeClozeStem(string $sentence, string $concept): ?string {
        $rx = '/' . preg_quote($concept, '/') . '/iu';
        if (!preg_match($rx, $sentence)) return null;
        $s = preg_replace($rx, '____', $sentence, 1);
        return "Fill in the blank based on the episode: \"" . self::clip($s, 200) . "\"";
    }

    /** Ensure unique options; keep first occurrence of each normalized value. */
    private static function uniqueList(array $opts): array {
        $seen = []; $out = [];
        foreach ($opts as $o) {
            $n = self::norm($o);
            if ($n === '' || isset($seen[$n])) continue;
            $seen[$n] = true;
            $out[] = $o;
        }
        return $out;
    }

    /**
     * Enhanced smart distractors with better diversity and quality
     */
    private static function smartDistractors(array $pool, array $wf, string $correct, int $k, array $avoidNorms = []): array {
        $cw = self::tokens($correct);
        $clen = count($cw);
        $cNorm = self::norm($correct);

        $scores = [];
        foreach ($pool as $p) {
            $n = self::norm($p);
            if ($n === $cNorm) continue;
            if (isset($avoidNorms[$n])) continue; // avoid previously used choices

            $tw = self::tokens($p);
            if (empty($tw)) continue;

            // length similarity (prefer similar length)
            $lenDiff = abs(count($tw) - $clen);
            $lenScore = max(0, 1.0 - (0.2 * $lenDiff));

            // frequency score (prefer moderately frequent terms)
            $freq = 0.0; 
            foreach ($tw as $w) $freq += $wf[$w] ?? 0.0;
            $freqScore = min(1.0, 0.3 + $freq);

            // overlap penalty (avoid too similar to correct answer)
            $overlap = count(array_intersect($tw, $cw));
            $overlapPenalty = $overlap >= min(2, $clen) ? 0.5 : 0.0;

            // semantic diversity bonus (prefer different word types)
            $semanticBonus = 0.0;
            if (count($tw) >= 2 && count($cw) >= 2) {
                $twTypes = array_map(fn($w) => self::getWordType($w), $tw);
                $cwTypes = array_map(fn($w) => self::getWordType($w), $cw);
                $typeOverlap = count(array_intersect($twTypes, $cwTypes));
                $semanticBonus = $typeOverlap === 0 ? 0.2 : 0.0;
            }

            // small jitter for randomization
            $jitter = mt_rand(0, 10) / 100.0;

            $scores[$p] = $lenScore * 0.4 + $freqScore * 0.4 - $overlapPenalty + $semanticBonus + $jitter;
        }
        arsort($scores);

        $out = [];
        foreach ($scores as $cand => $_) {
            $out[] = $cand;
            if (count($out) >= $k) break;
        }
        return $out;
    }

    /** Simple word type classification for semantic diversity */
    private static function getWordType(string $word): string {
        // Simple heuristic classification
        if (preg_match('/^(is|are|was|were|be|been|being|have|has|had|do|does|did|will|would|can|could|should|may|might)$/', $word)) {
            return 'verb_aux';
        }
        if (preg_match('/^(the|a|an|this|that|these|those|my|your|his|her|its|our|their)$/', $word)) {
            return 'determiner';
        }
        if (preg_match('/^(and|but|or|so|yet|for|nor)$/', $word)) {
            return 'conjunction';
        }
        if (preg_match('/^(in|on|at|by|for|with|from|to|of|about|under|over|through|during|before|after)$/', $word)) {
            return 'preposition';
        }
        if (preg_match('/^(very|quite|rather|extremely|highly|completely|totally|absolutely)$/', $word)) {
            return 'adverb';
        }
        return 'other';
    }

    /** Rank phrases that are context-related to the sentence/concept. */
    private static function relatedPhrases(array $phrases, string $contextSentence, string $concept): array {
        $ctxTok = self::tokens($contextSentence);
        $ctxSet = array_fill_keys($ctxTok, true);
        $conTok = self::tokens($concept);
        $conSet = array_fill_keys($conTok, true);

        $scores = [];
        foreach ($phrases as $p) {
            $pt = self::tokens($p);
            if (empty($pt)) continue;

            // co-occurrence with context sentence
            $overlap = 0;
            foreach ($pt as $w) if (isset($ctxSet[$w])) $overlap++;

            // keep some distance from the exact concept (avoid near-duplicates)
            $tooClose = 0;
            foreach ($pt as $w) if (isset($conSet[$w])) $tooClose++;

            $lenSim = 1.0 - min(0.6, abs(count($pt) - max(1, count($conTok))) * 0.15);
            $score = ($overlap * 0.7) + ($lenSim * 0.5) - ($tooClose >= 2 ? 0.4 : 0.0);

            $scores[$p] = $score;
        }
        arsort($scores);
        return array_slice(array_keys($scores), 0, 24);
    }

    /**
     * Enhanced quiz generation with diverse question types and better quality
     */
    public static function generateQuiz(string $text, int $numQ = 8): array {
        $numQ = max(3, min(20, $numQ));

        // Prep
        $sentences = array_values(array_filter(array_map('trim', self::sentences($text)), function($s){ 
            return mb_strlen($s) > 30; 
        }));
        $sentences = self::dedupeByNorm($sentences);

        $phrases = self::keyPhrases($text, max(80, $numQ * 10));
        $phrases = self::dedupeByNorm($phrases);
        
        if (!$phrases || !$sentences) {
            return ['title' => 'Quiz', 'questions' => []];
        }

        // Enhanced concept extraction with importance scoring
        $wf = self::wordFreq($text);
        $phraseScores = [];
        foreach ($phrases as $p) {
            $score = 0.0;
            foreach (self::tokens($p) as $w) $score += $wf[mb_strtolower($w)] ?? 0;
            // Boost score for longer, more specific phrases
            $score *= (1 + count(self::tokens($p)) * 0.1);
            $phraseScores[$p] = $score;
        }
        arsort($phraseScores);
        $topConcepts = array_slice(array_keys($phraseScores), 0, $numQ * 3);

        $questions = [];
        $usedStems = [];
        $usedAnswers = [];
        $usedOptionNorms = [];
        $usedQuestionTypes = [];

        // Enhanced question templates with more variety
        $templates = [
            'cloze' => 'Fill in the blank',
            'definition' => 'Definition/Meaning',
            'purpose' => 'Purpose/Role',
            'comparison' => 'Comparison/Contrast',
            'application' => 'Application/Example',
            'cause_effect' => 'Cause and Effect',
            'sequence' => 'Sequence/Order',
            'evaluation' => 'Evaluation/Assessment'
        ];

        $templateKeys = array_keys($templates);
        $ti = 0;

        foreach ($topConcepts as $concept) {
            if (count($questions) >= $numQ) break;

            $ctx = self::bestContextSentence($sentences, $concept);
            if (!$ctx) continue;

            // Rotate through question types for diversity
            $tpl = $templateKeys[$ti % count($templateKeys)];
            $ti++;

            // Build enhanced stem based on question type
            $stem = '';
            $explanation = self::generateConciseExplanation($concept, $ctx, $tpl);

            switch ($tpl) {
                case 'cloze':
                    $stem = self::makeClozeStem($ctx, $concept);
                    if (!$stem) $tpl = 'definition';
                    break;
                    
                case 'definition':
                    $stem = "According to the episode, what does \"" . self::clip($concept, 70) . "\" refer to?";
                    break;
                    
                case 'purpose':
                    $stem = "What is the primary purpose or role of \"" . self::clip($concept, 70) . "\" as discussed in the episode?";
                    break;
                    
                case 'comparison':
                    $stem = "How does \"" . self::clip($concept, 70) . "\" compare to other concepts mentioned in the episode?";
                    break;
                    
                case 'application':
                    $stem = "Which of the following best represents an application or example of \"" . self::clip($concept, 70) . "\"?";
                    break;
                    
                case 'cause_effect':
                    $stem = "What is the relationship between \"" . self::clip($concept, 70) . "\" and the outcomes discussed?";
                    break;
                    
                case 'sequence':
                    $stem = "In what order or sequence does \"" . self::clip($concept, 70) . "\" typically occur according to the episode?";
                    break;
                    
                case 'evaluation':
                    $stem = "How is \"" . self::clip($concept, 70) . "\" evaluated or assessed in the episode?";
                    break;
            }

            if (!$stem) continue;

            // Enhanced uniqueness guards
            $normStem = self::norm($stem);
            $normAns = self::norm($concept);
            if (isset($usedStems[$normStem]) || isset($usedAnswers[$normAns])) continue;

            // Build diverse options with enhanced logic
            $localPool = self::relatedPhrases($phrases, $ctx, $concept);
            $distractors = self::smartDistractors($localPool, $wf, $concept, 3, $usedOptionNorms);

            // Backfill from global pool if needed
            if (count($distractors) < 3) {
                $need = 3 - count($distractors);
                $more = self::smartDistractors($phrases, $wf, $concept, $need, $usedOptionNorms);
                $distractors = array_merge($distractors, $more);
                $distractors = self::uniqueList($distractors);
            }

            // Build final choices
            $choices = $distractors;
            $choices[] = $concept;
            $choices = self::uniqueList($choices);
            if (count($choices) < 4) continue;
            
            // Shuffle but ensure good distribution
            shuffle($choices);

            $answerIdx = array_search($concept, $choices, true);
            if ($answerIdx === false) continue;

            // Record used options for future questions
            foreach ($choices as $opt) {
                if ($opt === $concept) continue;
                $usedOptionNorms[self::norm($opt)] = true;
            }

            $questions[] = [
                'id' => 'q_' . substr(sha1($concept . $stem . $tpl), 0, 12),
                'question' => self::clip($stem, 250),
                'choices' => array_values($choices),
                'answer' => $answerIdx,
                'explanation' => $explanation,
                'type' => $tpl,
                'difficulty' => self::assessDifficulty($concept, $ctx, $wf),
                'concept' => $concept
            ];
            
            $usedStems[$normStem] = true;
            $usedAnswers[$normAns] = true;
            $usedQuestionTypes[$tpl] = ($usedQuestionTypes[$tpl] ?? 0) + 1;
        }

        // If still short, generate additional questions with different strategies
        if (count($questions) < $numQ) {
            $remaining = $numQ - count($questions);
            $additionalQuestions = self::generateAdditionalQuestions($text, $remaining, $usedAnswers, $usedOptionNorms, $wf);
            $questions = array_merge($questions, $additionalQuestions);
        }

        // Sort questions by difficulty for better user experience
        usort($questions, function($a, $b) {
            return $a['difficulty'] <=> $b['difficulty'];
        });

        return [
            'title' => 'Episode Quiz',
            'questions' => array_slice($questions, 0, $numQ),
            'metadata' => [
                'total_concepts' => count($topConcepts),
                'question_types' => $usedQuestionTypes,
                'difficulty_distribution' => self::getDifficultyDistribution($questions)
            ]
        ];
    }

    /** Generate additional questions when initial generation falls short */
    private static function generateAdditionalQuestions(string $text, int $count, array $usedAnswers, array $usedOptionNorms, array $wf): array {
        $sentences = self::sentences($text);
        $phrases = self::keyPhrases($text, 50);
        $questions = [];

        // Try different strategies for additional questions
        $strategies = ['factual', 'inferential', 'analytical'];
        
        foreach ($strategies as $strategy) {
            if (count($questions) >= $count) break;
            
            switch ($strategy) {
                case 'factual':
                    $questions = array_merge($questions, self::generateFactualQuestions($sentences, $phrases, $usedAnswers, $usedOptionNorms, $wf));
                    break;
                case 'inferential':
                    $questions = array_merge($questions, self::generateInferentialQuestions($sentences, $phrases, $usedAnswers, $usedOptionNorms, $wf));
                    break;
                case 'analytical':
                    $questions = array_merge($questions, self::generateAnalyticalQuestions($sentences, $phrases, $usedAnswers, $usedOptionNorms, $wf));
                    break;
            }
        }

        return array_slice($questions, 0, $count);
    }

    /** Generate factual questions based on specific statements */
    private static function generateFactualQuestions(array $sentences, array $phrases, array $usedAnswers, array $usedOptionNorms, array $wf): array {
        $questions = [];
        $factualIndicators = ['according to', 'the study shows', 'research indicates', 'data reveals', 'statistics show'];
        
        foreach ($sentences as $sentence) {
            if (count($questions) >= 3) break;
            
            $sentenceLower = mb_strtolower($sentence);
            $hasFactualIndicator = false;
            foreach ($factualIndicators as $indicator) {
                if (strpos($sentenceLower, $indicator) !== false) {
                    $hasFactualIndicator = true;
                    break;
                }
            }
            
            if (!$hasFactualIndicator) continue;
            
            // Extract key terms from the sentence
            $keyTerms = self::extractKeyTerms($sentence);
            if (empty($keyTerms)) continue;
            
            $concept = $keyTerms[0];
            if (isset($usedAnswers[self::norm($concept)])) continue;
            
            $stem = "According to the episode, which statement is true about \"" . self::clip($concept, 60) . "\"?";
            
            // Create choices based on the sentence and related concepts
            $choices = self::buildFactualChoices($sentence, $concept, $phrases, $usedOptionNorms, $wf);
            if (count($choices) < 4) continue;
            
            $questions[] = [
                'id' => 'q_fact_' . substr(sha1($concept . $stem), 0, 10),
                'question' => $stem,
                'choices' => $choices,
                'answer' => 0, // First choice is correct
                'explanation' => self::generateConciseExplanation($concept, $sentence, 'factual'),
                'type' => 'factual',
                'difficulty' => 'medium',
                'concept' => $concept
            ];
        }
        
        return $questions;
    }

    /** Generate inferential questions that require reasoning */
    private static function generateInferentialQuestions(array $sentences, array $phrases, array $usedAnswers, array $usedOptionNorms, array $wf): array {
        $questions = [];
        $inferentialWords = ['therefore', 'thus', 'consequently', 'as a result', 'this means', 'implies'];
        
        foreach ($sentences as $sentence) {
            if (count($questions) >= 2) break;
            
            $sentenceLower = mb_strtolower($sentence);
            $hasInferentialWord = false;
            foreach ($inferentialWords as $word) {
                if (strpos($sentenceLower, $word) !== false) {
                    $hasInferentialWord = true;
                    break;
                }
            }
            
            if (!$hasInferentialWord) continue;
            
            $keyTerms = self::extractKeyTerms($sentence);
            if (empty($keyTerms)) continue;
            
            $concept = $keyTerms[0];
            if (isset($usedAnswers[self::norm($concept)])) continue;
            
            $stem = "Based on the episode's discussion, what can be inferred about \"" . self::clip($concept, 60) . "\"?";
            
            $choices = self::buildInferentialChoices($sentence, $concept, $phrases, $usedOptionNorms, $wf);
            if (count($choices) < 4) continue;
            
            $questions[] = [
                'id' => 'q_inf_' . substr(sha1($concept . $stem), 0, 10),
                'question' => $stem,
                'choices' => $choices,
                'answer' => 0,
                'explanation' => self::generateConciseExplanation($concept, $sentence, 'inferential'),
                'type' => 'inferential',
                'difficulty' => 'hard',
                'concept' => $concept
            ];
        }
        
        return $questions;
    }

    /** Generate analytical questions that require deeper thinking */
    private static function generateAnalyticalQuestions(array $sentences, array $phrases, array $usedAnswers, array $usedOptionNorms, array $wf): array {
        $questions = [];
        $analyticalWords = ['analyze', 'evaluate', 'compare', 'contrast', 'assess', 'examine'];
        
        // Look for sentences that contain analytical language
        foreach ($sentences as $sentence) {
            if (count($questions) >= 2) break;
            
            $sentenceLower = mb_strtolower($sentence);
            $hasAnalyticalWord = false;
            foreach ($analyticalWords as $word) {
                if (strpos($sentenceLower, $word) !== false) {
                    $hasAnalyticalWord = true;
                    break;
                }
            }
            
            if (!$hasAnalyticalWord) continue;
            
            $keyTerms = self::extractKeyTerms($sentence);
            if (count($keyTerms) < 2) continue;
            
            $concept1 = $keyTerms[0];
            $concept2 = $keyTerms[1];
            
            if (isset($usedAnswers[self::norm($concept1)]) || isset($usedAnswers[self::norm($concept2)])) continue;
            
            $stem = "How do \"" . self::clip($concept1, 40) . "\" and \"" . self::clip($concept2, 40) . "\" relate to each other according to the episode?";
            
            $choices = self::buildAnalyticalChoices($sentence, $concept1, $concept2, $phrases, $usedOptionNorms, $wf);
            if (count($choices) < 4) continue;
            
            $questions[] = [
                'id' => 'q_anal_' . substr(sha1($concept1 . $concept2 . $stem), 0, 10),
                'question' => $stem,
                'choices' => $choices,
                'answer' => 0,
                'explanation' => self::generateConciseExplanation($concept1 . ' vs ' . $concept2, $sentence, 'analytical'),
                'type' => 'analytical',
                'difficulty' => 'hard',
                'concept' => $concept1 . ' vs ' . $concept2
            ];
        }
        
        return $questions;
    }

    /** Extract key terms from a sentence */
    private static function extractKeyTerms(string $sentence): array {
        $phrases = self::keyPhrases($sentence, 5);
        return array_filter($phrases, function($phrase) {
            return mb_strlen($phrase) > 3 && mb_strlen($phrase) < 50;
        });
    }

    /** Build choices for factual questions */
    private static function buildFactualChoices(string $sentence, string $concept, array $phrases, array $usedOptionNorms, array $wf): array {
        $correct = "The episode states: \"" . self::clip($sentence, 120) . "\"";
        $choices = [$correct];
        
        // Generate plausible but incorrect alternatives
        $distractors = self::smartDistractors($phrases, $wf, $concept, 3, $usedOptionNorms);
        foreach ($distractors as $distractor) {
            $choices[] = "The episode mentions \"" . $distractor . "\" as related to this topic";
        }
        
        return $choices;
    }

    /** Build choices for inferential questions */
    private static function buildInferentialChoices(string $sentence, string $concept, array $phrases, array $usedOptionNorms, array $wf): array {
        $correct = "It can be inferred that " . self::clip($sentence, 100);
        $choices = [$correct];
        
        $distractors = self::smartDistractors($phrases, $wf, $concept, 3, $usedOptionNorms);
        foreach ($distractors as $distractor) {
            $choices[] = "It suggests that " . $distractor . " is the main factor";
        }
        
        return $choices;
    }

    /** Build choices for analytical questions */
    private static function buildAnalyticalChoices(string $sentence, string $concept1, string $concept2, array $phrases, array $usedOptionNorms, array $wf): array {
        $correct = "They are " . self::clip($sentence, 100);
        $choices = [$correct];
        
        $analyticalOptions = [
            "They are completely independent concepts",
            "They are identical in meaning and function", 
            "They are opposites that cannot coexist",
            "They are sequential steps in a process"
        ];
        
        $choices = array_merge($choices, $analyticalOptions);
        return array_slice($choices, 0, 4);
    }

    /** Assess question difficulty based on concept complexity and context */
    private static function assessDifficulty(string $concept, string $context, array $wf): string {
        $conceptTokens = self::tokens($concept);
        $contextTokens = self::tokens($context);
        
        // Factors that increase difficulty
        $complexityScore = 0;
        
        // Longer concepts are generally harder
        $complexityScore += count($conceptTokens) * 0.1;
        
        // Technical terms increase difficulty
        $technicalTerms = ['algorithm', 'methodology', 'framework', 'paradigm', 'hypothesis', 'analysis'];
        foreach ($conceptTokens as $token) {
            if (in_array($token, $technicalTerms)) {
                $complexityScore += 0.3;
            }
        }
        
        // Low frequency terms are harder
        $avgFreq = 0;
        foreach ($conceptTokens as $token) {
            $avgFreq += $wf[$token] ?? 0;
        }
        $avgFreq /= max(1, count($conceptTokens));
        $complexityScore += (1 - $avgFreq) * 0.5;
        
        if ($complexityScore < 0.3) return 'easy';
        if ($complexityScore < 0.7) return 'medium';
        return 'hard';
    }

    /** Get difficulty distribution of questions */
    private static function getDifficultyDistribution(array $questions): array {
        $distribution = ['easy' => 0, 'medium' => 0, 'hard' => 0];
        foreach ($questions as $q) {
            $distribution[$q['difficulty']]++;
        }
        return $distribution;
    }

    /** Extractive summary. */
    public static function summarize(string $text, float $ratio = 0.18, int $maxSentences = 10): string {
        $sentences = self::sentences($text);
        if (count($sentences) <= 3) return $text;

        $freq = self::wordFreq($text);
        $scores = self::scoreSentences($sentences, $freq);

        $k = max(3, min($maxSentences, (int)ceil($ratio * count($sentences))));
        arsort($scores);
        $topIdx = array_slice(array_keys($scores), 0, $k);
        sort($topIdx); // keep original order
        $picked = array_map(fn($i) => $sentences[$i], $topIdx);

        return implode(' ', $picked);
    }

    /** Bullet note extraction (top sentences + short-ify). */
    public static function makeNotes(string $text, int $bullets = 8): array {
        $sentences = self::sentences($text);
        $freq = self::wordFreq($text);
        $scores = self::scoreSentences($sentences, $freq);

        arsort($scores);
        $idx = array_slice(array_keys($scores), 0, min($bullets, count($sentences)));
        sort($idx);
        $raw = array_map(fn($i) => self::shortenSentence($sentences[$i]), $idx);

        // Deduplicate-ish
        $seen = [];
        $out = [];
        foreach ($raw as $s) {
            $key = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', $s));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = $s;
        }
        return $out;
    }

    /** Simple RAKE-like keyphrase extraction. */
    public static function keyPhrases(string $text, int $max = 12): array {
        $stop = self::stopwords();
        $textLow = mb_strtolower($text, 'UTF-8');
        $tokens = preg_split('/([^a-z0-9]+)/u', $textLow, -1, PREG_SPLIT_DELIM_CAPTURE);
        $phrases = [];
        $current = [];
        foreach ($tokens as $tok) {
            if (!preg_match('/[a-z0-9]/', $tok)) { // delimiter
                if ($current) { $phrases[] = $current; $current = []; }
                continue;
            }
            if (isset($stop[$tok])) {
                if ($current) { $phrases[] = $current; $current = []; }
            } else {
                $current[] = $tok;
            }
        }
        if ($current) $phrases[] = $current;

        $wordDeg = []; $wordFreq = [];
        foreach ($phrases as $p) {
            $deg = count($p);
            foreach ($p as $w) {
                $wordFreq[$w] = ($wordFreq[$w] ?? 0) + 1;
                $wordDeg[$w]  = ($wordDeg[$w]  ?? 0) + $deg;
            }
        }
        $wordScore = [];
        foreach ($wordFreq as $w => $f) $wordScore[$w] = ($wordDeg[$w] ?? 0) / max(1, $f);

        $phraseScore = [];
        foreach ($phrases as $p) {
            $s = 0.0; foreach ($p as $w) $s += $wordScore[$w] ?? 0;
            $k = implode(' ', $p);
            $phraseScore[$k] = max($phraseScore[$k] ?? 0, $s);
        }

        arsort($phraseScore);
        $out = array_slice(array_keys($phraseScore), 0, $max);
        // Capitalize nicely
        $out = array_map(fn($x) => preg_replace_callback('/\b[a-z]/', fn($m)=>strtoupper($m[0]), $x), $out);
        return $out;
    }

    /** Content kit (titles, TL;DR, tweets, LinkedIn, description, tags). */
    public static function contentKit(string $text): array {
        $phr = self::keyPhrases($text, 12);
        $top = array_slice($phr, 0, 3);
        $main = $top ? implode(', ', $top) : 'Key Ideas';

        // Titles
        $titles = [
            "Understanding " . ($top[0] ?? 'the Big Idea'),
            "The Future of " . ($top[0] ?? 'This Topic'),
            ($top[1] ?? 'Key Insight') . ": What You Need to Know",
            "From Basics to Breakthroughs in " . ($top[0] ?? 'Today\'s Topic'),
            "A Practical Guide to " . ($top[0] ?? 'Getting Started')
        ];

        // TL;DR bullets
        $tldr = array_slice(self::makeNotes($text, 6), 0, 5);

        // Tweet thread (<= 280 chars per item)
        $thread = [];
        $intro = "Thread: " . ($titles[0]) . " 🧵";
        $thread[] = self::clip($intro, 275);
        foreach ($tldr as $b) $thread[] = self::clip("• " . $b, 275);
        $thread[] = self::clip("Takeaway: " . ($titles[2]) . " #podcast #learning", 275);

        // LinkedIn post
        $li = "Key insights on {$main}:\n";
        foreach ($tldr as $b) $li .= "• {$b}\n";
        $li .= "\nIf you found this helpful, share with a friend. #podcast #insights";

        // Description + tags
        $desc = $titles[3] . ". In this episode we cover: " . strtolower($main) . ".";
        $tags = array_map(fn($p) => preg_replace('/\s+/', '', mb_strtolower($p)), array_slice($phr, 0, 10));

        return [
            'titles' => $titles,
            'tldr' => $tldr,
            'thread' => $thread,
            'linkedin' => $li,
            'description' => $desc,
            'tags' => $tags
        ];
    }

    /** Helpers */
    private static function shortenSentence(string $s): string {
        // Trim leading fillers & keep within ~180 chars
        $s = preg_replace('/^(and|but|so|well|um|uh)\b/i', '', trim($s));
        return self::clip($s, 180);
    }
    
    private static function clip(string $s, int $limit): string {
        if (mb_strlen($s) <= $limit) return $s;
        return rtrim(mb_substr($s, 0, $limit - 1)) . '…';
    }

    /** Generate concise, actionable learning objectives from a transcript. */
    public static function learningObjectives(string $text, int $count = 6): array {
        $phrases = self::keyPhrases($text, max(12, $count * 3));
        if (!$phrases) return [];

        // Common Bloom-style verbs (balanced mix)
        $verbs = ['Understand','Explain','Identify','Compare','Apply','Evaluate','Recognize','Describe','Outline','Summarize','Differentiate','Discuss'];
        $out = [];
        $used = [];

        foreach ($phrases as $i => $p) {
            if (count($out) >= $count) break;
            $verb = $verbs[$i % count($verbs)];
            $obj  = preg_replace('/\b(introduction|basics|overview)\b/i', '', $p);
            $obj  = trim($obj);
            if (mb_strlen($obj) < 3) continue;
            $key = mb_strtolower($obj);
            if (isset($used[$key])) continue;
            $used[$key] = true;

            // Make it "learners will be able to …"
            $objective = "{$verb} " . self::normalizeNounPhrase($obj);
            $out[] = $objective;
        }

        // Pad if needed by splitting top phrases
        while (count($out) < max(3, $count) && count($phrases) > 0) {
            $p = array_shift($phrases);
            $verb = $verbs[array_rand($verbs)];
            $out[] = "{$verb} " . self::normalizeNounPhrase($p);
        }
        return $out;
    }

    /** ---------- private helpers for the above ---------- */
    private static function normalizeNounPhrase(string $s): string {
        // Lower noise, fix capitalization
        $s = preg_replace('/\s+/', ' ', trim($s));
        // Capitalize first letter of words >2 chars
        $s = preg_replace_callback('/\b([a-z])([a-z]{2,})\b/u', function($m){
            return strtoupper($m[1]) . $m[2];
        }, mb_strtolower($s));
        // Prefer singular-ish phrasing heuristically (light touch)
        $s = preg_replace('/\b(Basics|Foundations|Principles)\b/u', 'Principles', $s);
        return $s;
    }

    private static function pickDistractors(array $pool, string $correct, int $k): array {
        $pool = array_values(array_filter($pool, function($p) use ($correct){
            $a = mb_strtolower($p); $b = mb_strtolower($correct);
            if ($a === $b) return false;
            // Filter near-duplicates
            $aC = preg_replace('/[^a-z0-9]/i','', $a);
            $bC = preg_replace('/[^a-z0-9]/i','', $b);
            if ($aC === $bC) return false;
            // Avoid trivial stop-phrases
            return mb_strlen($p) >= 3;
        }));
        shuffle($pool);
        return array_slice($pool, 0, $k);
    }
}
