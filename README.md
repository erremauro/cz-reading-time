# CZ Reading Time

**CZ Reading Time** è un plugin WordPress che mostra automaticamente il tempo di lettura totale del post e il tempo di lettura delle singole sezioni sotto gli `h2`.

---

## Funzionalità principali

- Tempo di lettura totale mostrato sotto il sottotitolo del singolo post.
- Tempo di lettura sezione mostrato sotto ogni `h2`.
- Calcolo basato sulla formula `numero di parole / parole al minuto`.
- Conteggio parole sul contenuto renderizzato, ignorando la formattazione HTML.
- Esclusione automatica dei contenuti dentro `div.footnotes`.
- Calcolo totale eseguito sull'intero post anche in presenza di paginazione `<!--nextpage-->`.
- Configurazione admin in una pagina dedicata **Impostazioni > CZ Reading Time**.
- Link rapido `Impostazioni` nella lista plugin.
- Valore predefinito: `250`.

---

## Requisiti

- WordPress 6.x
- PHP 7.4+

---

## Installazione

1. Copia la cartella `cz-reading-time` in `wp-content/plugins/`.
2. Attiva il plugin da **Plugin > Plugin installati**.
3. Configura il valore parole/minuto in **Impostazioni > CZ Reading Time**, se necessario.
4. Abilita la preferenza utente `czup_show_readingtime` per mostrare i tempi di lettura.

---

## Note

- Il formato restituito è del tipo `15 minuti` oppure `1 ora e 5 minuti`.
- Se il contenuto non contiene parole, il plugin restituisce `0 minuti`.
- I tempi di sezione vengono calcolati dal contenuto successivo all'`h2` fino al prossimo `h2` o alla fine della pagina corrente.
