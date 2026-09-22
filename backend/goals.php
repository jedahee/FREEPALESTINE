<?php
/**
 * Meta común de la campaña.
 *
 * Una sola gran meta en lugar de hitos progresivos: 500.000 firmas, el umbral
 * legal que exige la Ley Orgánica 3/1984 para que una Iniciativa Legislativa
 * Popular (ILP) sea admitida a trámite en el Congreso de los Diputados.
 * Al alcanzarla, cederemos todas las firmas a asociaciones pro-palestinas
 * para registrar la ILP y que el Estado español tenga que escuchar.
 */
function goals() {
    return [
        [
            "signatures" => 500000,
            "deliverable" => "ilp",
        ],
    ];
}

$goals = goals();

?>
