<h2 style="text-align: center;">Sephpa Begleitzettel</h2>

<table style="margin-top: 2cm; border: 0; width: 100%;">
    <tr>
        <td>Dateiname</td>
        <td style="text-align: right;">{{file_name}}</td>
    </tr>
    <tr>
        <td>Schemaversion</td>
        <td style="text-align: right;">{{scheme_version}}</td>
    </tr>
    <tr>
        <td>Zahlungsart</td>
        <td style="text-align: right;">{{payment_type}}</td>
    </tr>
    <tr>
        <td>Nachrichten-ID</td>
        <td style="text-align: right;">{{message_id}}</td>
    </tr>
    <tr>
        <td>Erstellungsdatum und -zeit</td>
        <td style="text-align: right;">{{creation_date_time}}</td>
    </tr>
    <tr>
        <td>Auftraggeber</td>
        <td style="text-align: right;">{{initialising_party}}</td>
    </tr>
    <tr>
        <td>Sammlerreferenz</td>
        <td style="text-align: right;">{{collection_reference}}</td>
    </tr>
    {{ifdef bic}}
    <tr>
        <td>BIC</td>
        <td style="text-align: right;">{{bic}}</td>
    </tr>
    {{endif bic}}
    <tr>
        <td>IBAN</td>
        <td style="text-align: right;">{{iban}}</td>
    </tr>
    <tr>
        <td>Ausführungstermin</td>
        <td style="text-align: right;">{{due_date}}</td>
    </tr>
    <tr>
        <td>Anzahl der Zahlungssätze</td>
        <td style="text-align: right;">{{number_of_transactions}}</td>
    </tr>
    <tr>
        <td>Summe der Beträge</td>
        <td style="text-align: right;">{{control_sum}}</td>
    </tr>
</table>

<table style="width: 100%; margin-top: 2cm; border: 0 solid;">
    <tr>
        <td></td>
        <td style="width: 25%; border-bottom: 1px solid; text-align: right;"></td>
        <td> {{current_date}}</td>
        <td style="width: 35%; border-bottom: 1px solid;"></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: center;">Ort</td>
        <td></td>
        <td style="text-align: center;">Unterschrift</td>
        <td></td>
    </tr>
</table>
