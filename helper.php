<?php
/**
 * Class de fonctions static pour gérer les macros
 */
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

class Helper
{

    /**
     * Remplacement interprétation des MACROS trouvées dans la chaîne
     * - {$sql: select name from fex_contacts where id = '{id}'}
     *
     * @param string $chaine
     * @param array() $items variables {"name": value}
     * @param boolean pour doubler les quotes ou non
     * @return string la chaîne remplacée
     */
    public static function macro($chaine_a_remplacer, $items, $b_escape = false)
    {
        global $tableur;
        if (strlen(trim($chaine_a_remplacer)) == 0) {
            return '';
        }

        $result = "";

        try
        {
            // Remplacement du #p1 
            if (isset($_REQUEST['p1'])) :
                $chaine = str_replace('#p1', $_REQUEST['p1'], $chaine_a_remplacer);
            else:
                $chaine = $chaine_a_remplacer;
            endif;

            // parcours de la chaine
            $len_chaine = strlen($chaine);
            for ($i = 0; $i < $len_chaine; $i++):
                $car = $chaine[$i];
                switch ($car):
            	case '{':
					$car = $chaine[$i + 1];
					// recherche de la fin de l'accolade corresponde (au même niveau)
					$pos_fin_accolade = 0;
					$niveau = 0;
					for ($j = $i + 1; $pos_fin_accolade == 0 && $j < $len_chaine; $j++) {
							switch ($chaine[$j]) {
								case '{':
									$niveau++;
									break;
								case '}':
									if ($niveau > 0) {
										$niveau--;
										break;
									} // endif
									$pos_fin_accolade = $j;
									break;
							} // end switch
					} // end for
					if ($pos_fin_accolade == 0) {
                        $tableur->add_error(__('End brace not found', 'tbr') . ' ' . $chaine);
						return '';
					}

					// $var contient le texte entre les accolades de même niveau (sans les accolades)
					$var = substr($chaine, $i + 1, $pos_fin_accolade - $i - 1);

					// macro avec un $ {$macro ... }
					if ($car == '$') {
						// traitement des macros imbriquées
						if (preg_match('/{/', $var)) {
							$var = Helper::macro($var, $items);
						} // endif
						$result .= Helper::macro_dollar($var);
						$i = $pos_fin_accolade;
						break;
					} // endif

					/*
					* Exception pour la rubrique PDF qui utilise le plugin mPDF
					* Il ne faut pas macrotiser {PAGENO}/{nbpg}
					* dom classe {
					*    style: valeur;
					* }
					*/
					if (preg_match('/^pageno$/i', $var)) {
						$result .= '{' . $var . '}';
						$i = $pos_fin_accolade;
						break;
					}
					if (preg_match('/^nb$/i', $var)) {
						$result .= '{' . $var . '}';
						$i = $pos_fin_accolade;
						break;
					}
					if (preg_match('/^ |\n|\r/', $var)) // blanc ou CRLF derrière l'accolade ouvrante
					{
						$result .= '{' . $var . '}';
						$i = $pos_fin_accolade;
						break;
					}

					// c'est une rubrique simple {nom_rubrique}

					/* @var $actrubrique Actrubrique */
					$b_var_trouve = false;
					foreach ($items as $key => $value) {
						if ($key == $var) {
							$b_var_trouve = true;

							if ($b_escape) {
								$result .= str_replace("'", "''", $value);
							} else {
								$result .= $value;
							}
						}
					}
					if ($b_var_trouve === false) {
                        $tableur->add_error(__('Element not found', 'tbr') . ' {' . $var . '}');
					}
					$i = $pos_fin_accolade;
					break;
				case '}':
					$i++;
					break;
				default:
					$result .= $car;
					endswitch;
            endfor;
        } catch (Exception $e) {
            $tableur->add_error($e->getMessage());
        } // end catch

        return $result;
    } // macro

    /**
     * Remplacement de toutes les macro $ du texte
     * @param string $chaine_a_remplacer
     * @param array() variables {"name": value}
     * @return string la chaîne remplacée
     */
    public static function macro_dollars(&$chaine, $items)
    {
        global $tableur;
        if (strlen(trim($chaine, " \n\r\0\x0B")) == 0) {
            return '';
        }

        $result = "";

        try
        {
            // parcour de la chaine
            $len_chaine = strlen($chaine);
            for ($i = 0; $i < $len_chaine; $i++) {
                $car = $chaine[$i];
                switch ($car) {
                    case '{':
                        $car = $chaine[$i + 1];

                        // recherche de la fin de l'accolade corresponde (au même niveau)
                        $pos_fin_accolade = 0;
                        $niveau = 0;
                        for ($j = $i + 1; $pos_fin_accolade == 0 && $j < $len_chaine; $j++) {
                            switch ($chaine[$j]) {
                                case '{':
                                    $niveau++;
                                    break;
                                case '}':
                                    if ($niveau > 0) {
                                        $niveau--;
                                        break;
                                    } // endif
                                    $pos_fin_accolade = $j;
                                    break;
                            } // end switch
                        } // end for
                        if ($pos_fin_accolade == 0) {
                            $tableur->add_error(__('End brace not found', 'tbr') . ' ' . $chaine);
                            return '';
                        }

                        // $var contient le texte entre les accolades de même niveau (sans les accolades)
                        $var = substr($chaine, $i + 1, $pos_fin_accolade - $i - 1);

                        // macro avec un $ {$macro ... }
                        if ($car == '$') {
                            // traitement des macros imbriquées
                            if (preg_match('/{/', $var)) {
                                $var = Helper::macro_dollars($var, $items);
                            } // endif
                            $result .= Helper::macro_dollar($var);
                            $i = $pos_fin_accolade;
                            break;
                        } else {
                            $result .= '{' . $car;
                        } // endif
                        $i++;
                        break;
                    default:
                        $result .= $car;
                } // end switch
            } // endfor
        } catch (Exception $e) {
            $tableur->add_error($e->getMessage());
        } // end catch

        return $result;
    } // macro

    /**
     * Remplacement des macros qui commencent par un $
     * @param string $chaine avec la macro {$macro : argument sans macro}
     * @return string la chaîne remplacée
     */
    public static function macro_dollar($chaine)
    {
        global $wpdb;
        global $tableur;
        // resulat dans
        $result = '';
        $is_found = false;

        $ipos = strpos($chaine, ':');
        $macro = $ipos === false ? strtolower($chaine) : strtolower(substr($chaine, 0, $ipos));

        /*
         * macro en minuscule pour les tests
         */
        switch ($macro):

            case '$egal':
            $is_found = true;
            if (preg_match('/^\$egal:(?P<PARAM>.+)$/', $chaine, $param_a)) {
                $isNotEgal = false;
                $var_a = explode(',', $param_a['PARAM']);
                if (isset($var_a[0])) {
                    $par = trim($var_a[0]);
                    $isNotEgal = false;
                    // si ! à la fin "égal" devient un "non égal"
                    if (preg_match('/!$/', $par)) {
                        $isNotEgal = true;
                        $par = str_replace('!', '', $par);
                    }
                    foreach ($var_a as $key => $var) {
                        if ($key == '0') {
                            continue;
                        }

                        if (trim($var) == $par) {
                            $result = $par;
                            break;
                        }
                    }
                    if ($isNotEgal) {
                        if (empty($result)) {
                            $result = $par;
                        } else {
                            $result = '';
                        }

                    }
                }
            } else {
                $result = '';
            }
            break;
               
            case '$entrecrochets':
            $is_found = true;
            $chaine = trim(substr($chaine, strlen('$entrecrochets:')));
            $result = Helper::get_entre_crochets($chaine);
            break;

            case '$entreparentheses':
            $is_found = true;
            $chaine = trim(substr($chaine, strlen('$entreparentheses:')));
            $result = Helper::get_entre_parentheses($chaine);
            break;

            case '$if':
            $is_found = true;
            // on tests d'abord avec le séparateur §
            $ret = preg_match('/^\$if:(?P<CONDITION>[^§]+)§(?P<BLOC>.*)/is', $chaine, $if_a);
            if ($ret) {
                if (trim($if_a['CONDITION']) == '') {
                    $result = '';
                } else {
                    $result = $if_a['BLOC'];
                }

            } else {
                $ret = preg_match('/^\$if:(?P<CONDITION>[^,]+),(?P<BLOC>.*)/is', $chaine, $if_a);
                if ($ret) {
                    $condition = trim($if_a['CONDITION']);
                    if ($condition == '') {
                        $result = '';
                    } else {
                        if ($condition === '!') {
                            $result = $if_a['BLOC'];
                        } elseif ($condition[strlen($condition) - 1] === '!' || $condition[0] === '!') {
                            $result = '';
                        } else {
                            $result = $if_a['BLOC'];
                        }
                    }
                } else {
                    $result = '';
                }
            }
            break;

            case '$listehtml':
            $is_found = true;
            $groupe_match = trim(substr($chaine, strlen('$listehtml:')));
            $liste_a = explode(',', $groupe_match);
            $html_a = array();
            if (!empty($liste_a)) {
                $html_a[] = '<ul>';
                foreach ($liste_a as $item) {
                    if (trim($item)) {
                        $html_a[] .= '<li class="text-muted"> ' . $item . ' </li>';
                    }

                }
                $html_a[] .= '</ul>';
            }
            $result = implode(' ', $html_a);
            break;

            case '$listesql':
            $is_found = true;
            $sql = substr($chaine, strlen('$listesql:'));
            $value_aa = $wpdb->get_results($wpdb->prepare($sql, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            if ( strlen($wpdb->last_error) > 0 ) :
                $tableur->add_error($wpdb->last_error);
                $tableur->add_error($wpdb->last_query);
            else:
                foreach ($value_aa as $value_a) {
                    foreach ($value_a as $value) {
                        $result .= empty($result) ? $value : ',' . $value;
                    }
                }
            endif;
            break;

            case '$listesqlcrlf':
            $is_found = true;
            $sql = substr($chaine, strlen('$listesqlcrlf:'));
            $value_aa = $wpdb->get_results($wpdb->prepare($sql, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            if ( strlen($wpdb->last_error) > 0 ) :
                $tableur->add_error($wpdb->last_error);
                $tableur->add_error($wpdb->last_query);
            else:
                foreach ($value_aa as $value_a) {
                    foreach ($value_a as $value) {
                        $result .= empty($result) ? $value : PHP_EOL . $value;
                    }
                }
            endif;
            break;

            case '$markdown':
            $is_found = true;
            $text = substr($chaine, strlen('$markdown:'));
            if (!class_exists('Parsedown')) :
                require_once dirname(__FILE__) . '/lib/Parsedown.php';
            endif;        
            $Parsedown = new Parsedown();
            $result = $Parsedown->text($text);
            break;
            
            case '$request':
            $is_found = true;
            $text = substr($chaine, strlen('$request:'));
            $result = $_REQUEST[$text];
            break;

            case '$sql':
            $is_found = true;
            $sql = substr($chaine, strlen('$sql:'));
            $value_aa = $wpdb->get_results($wpdb->prepare($sql, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            if ( strlen($wpdb->last_error) > 0 ) :
                $tableur->add_error($wpdb->last_error);
                $tableur->add_error($wpdb->last_query);
            else:
                foreach ($value_aa as $value_a) {
                    foreach ($value_a as $value) {
                        $result = $value;
                        break;
                    }
                    break;
                }
            endif;
            break;

            // macros wordpress
            case '$date':
            $is_found = true;
            $result = current_time( 'mysql' );
            break;

            case '$user_email':
            $is_found = true;
            $result = wp_get_current_user()->user_email;
            break;

            case '$user_name':
            $is_found = true;
            $result = wp_get_current_user()->display_name;
            break;
    
        endswitch;
        if ( ! $is_found ) {
            $tableur->add_error(__('Macro not found', 'tbr') . ' {' . $chaine . '}');
        }
        return $result;
    }

    /*
     * récupération des caractères entre parenthèses dans la chaine passée en paramètre
     */
    public static function get_entre_parentheses($chaine)
    {
        $liste_a = explode("(", $chaine);
        if (!isset($liste_a[1])) {
            return $chaine;
        } else {
            $liste2_a = explode(")", $liste_a[1]);
            return $liste2_a[0];
        }
    }

    public static function get_entre_crochets($chaine)
    {
        $liste_a = explode("[", $chaine);
        if (!isset($liste_a[1])) {
            return $chaine;
        } else {
            $liste2_a = explode("]", $liste_a[1]);
            return $liste2_a[0];
        }
    }

    /**
     * retourne le code d'une table HTML sans les données (sans tbody)
     * @param array[][] $value_aa
     * @return string
     */
    public static function table_to_html(&$value_aa, $table_id = 'datatable')
    {
        $class = 'table table-bordered table-condensed';
        // D'abord les entêtes
        $compteur = 0;
        $entete = '   <tr>' . PHP_EOL;
        $corps = '';
        $html = '';

        foreach ($value_aa as $ligne) {
            $compteur++;
            $corps .= '  <tr>' . PHP_EOL;
            foreach ($ligne as $key => $valeur) {
                if ($compteur == 1) {
                    $entete .= '    <th>' . $key . '</th>' . PHP_EOL;
                }
                $corps .= '    <td>' . html_escape($valeur) . '</td>' . PHP_EOL;
            }
            if ($compteur == 1) {
                $entete .= '  </tr>';
            }
            $corps .= '  </tr>' . PHP_EOL;
        }
        $html = '<table id="' . $table_id . '" class="' . $class . '">' . PHP_EOL;
        $html .= '<thead>' . PHP_EOL . $entete . PHP_EOL . '</thead>' . PHP_EOL;
        $html .= '<tbody>' . PHP_EOL . $corps . PHP_EOL . '</tbody>' . PHP_EOL;
        $html .= '</table>';
        return $html;
    }

    /**
     * retourne le code d'une table HTML sans les données (sans tbody)
     * @param array[][] $value_aa
     * @return string
     */
    public static function table_entete_to_html(&$value_aa, $table_id = 'datatable')
    {
        $class = 'table table-bordered table-hover table-condensed';
        // D'abord les entêtes
        $compteur = 0;
        $entete = '   <tr>' . PHP_EOL;
        //$corps = '';
        $html = '';

        foreach ($value_aa as $ligne) {
            $compteur++;
            //$corps .= '  <tr>'.PHP_EOL;
            foreach ($ligne as $key => $valeur) {
                if ($compteur == 1) {
                    $entete .= '    <th>' . $key . '</th>' . PHP_EOL;
                }
                //$corps .= '    <td>'.html_escape($valeur).'</td>'.PHP_EOL;
            }
            if ($compteur == 1) {
                $entete .= '  </tr>';
                break;
            }
            //$corps .= '  </tr>'.PHP_EOL;
        }
        $html = '<table id="' . $table_id . '" class="' . $class . '">' . PHP_EOL;
        $html .= '<thead>' . PHP_EOL . $entete . PHP_EOL . '</thead>' . PHP_EOL;
        //$html .= '<tbody>'.PHP_EOL.$corps.PHP_EOL.'</tbody>'.PHP_EOL;
        $html .= '</table>';
        return $html;
    }

}
