<?php
/**
 * giua@school
 *
 * Copyright (c) 2017-2019 Antonello Dessì
 *
 * @author    Antonello Dessì
 * @license   http://www.gnu.org/licenses/agpl.html AGPL
 * @copyright Antonello Dessì 2017-2019
 */


namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Docente;
use App\Entity\Classe;


/**
 * Cattedra - repository
 */
class CattedraRepository extends EntityRepository {

  /**
   * Restituisce la lista dei docenti secondo i criteri di ricerca indicati
   *
   * @param array $search Lista dei criteri di ricerca
   * @param int $page Pagina corrente
   * @param int $limit Numero di elementi per pagina
   *
   * @return Paginator Oggetto Paginator
   */
  public function findAll($search=null, $page=1, $limit=10) {
    // crea query base
    $query = $this->createQueryBuilder('c')
      ->join('c.classe', 'cl')
      ->join('c.materia', 'm')
      ->join('c.docente', 'd')
      ->orderBy('cl.anno,cl.sezione,m.nomeBreve,d.cognome,d.nome', 'ASC');
    if ($search['classe'] > 0) {
      $query->where('cl.id=:classe')->setParameter('classe', $search['classe']);
    }
    if ($search['materia'] > 0) {
      $query->andwhere('m.id=:materia')->setParameter('materia', $search['materia']);
    }
    if ($search['docente'] > 0) {
      $query->andwhere('d.id=:docente')->setParameter('docente', $search['docente']);
    }
    // crea lista con pagine
    return $this->paginate($query->getQuery(), $page, $limit);
  }

  /**
   * Paginatore dei risultati della query
   *
   * @param Query $dql Query da mostrare
   * @param int $page Pagina corrente
   * @param int $limit Numero di elementi per pagina
   *
   * @return Paginator Oggetto Paginator
   */
  public function paginate($dql, $page=1, $limit=10) {
    $paginator = new Paginator($dql);
    $paginator->getQuery()
      ->setFirstResult($limit * ($page - 1))
      ->setMaxResults($limit);
    return $paginator;
  }

  /**
   * Restituisce la lista delle cattedre del docente indicato
   *
   * @param Docente $docente Docente di cui recuperare le cattedre
   * @param string $tipo Tipo di formattazione dei dati desiderata [Q=risultato query,C=form ChoiceType,A=array associativo]
   *
   * @return Array Dati formattati in un array associativo
   */
  public function cattedreDocente(Docente $docente, $tipo='A') {
    $dati = array();
    // lista cattedre
    $cattedre = $this->createQueryBuilder('c')
      ->join('c.classe', 'cl')
      ->join('c.materia', 'm')
      ->leftJoin('c.alunno', 'a')
      ->where('c.docente=:docente AND c.attiva=:attiva')
      ->orderBy('cl.anno,cl.sezione,m.nomeBreve,a.cognome,a.nome', 'ASC')
      ->setParameters(['docente' => $docente, 'attiva' => 1])
      ->getQuery()
      ->getResult();
    // formato dati
    if ($tipo == 'Q') {
      // risultato query
      $dati = $cattedre;
    } elseif ($tipo == 'C') {
      // form ChoiceType
      foreach ($cattedre as $cat) {
        $label = $cat->getClasse()->getAnno().'ª '.$cat->getClasse()->getSezione().' - '.$cat->getMateria()->getNomeBreve().
          ($cat->getAlunno() ? ' ('.$cat->getAlunno()->getCognome().' '.$cat->getAlunno()->getNome().')' : '');
        $dati[$label] = $cat;
      }
    } else {
      // array associativo
      $dati['choice'] = array();
      $dati['lista'] = array();
      foreach ($cattedre as $cat) {
        $label = $cat->getClasse()->getAnno().'ª '.$cat->getClasse()->getSezione().' - '.$cat->getMateria()->getNomeBreve().
          ($cat->getAlunno() ? ' ('.$cat->getAlunno()->getCognome().' '.$cat->getAlunno()->getNome().')' : '');
        $dati['choice'][$label] = $cat;
        $dati['lista'][$cat->getId()]['object'] = $cat;
        $dati['lista'][$cat->getId()]['label'] = $label;
      }
    }
    // restituisce dati
    return $dati;
  }

}
