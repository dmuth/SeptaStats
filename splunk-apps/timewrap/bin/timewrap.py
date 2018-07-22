# Copyright (C) 2005-2013 Splunk Inc. All Rights Reserved.  Version 4.0
import re, sys, time, csv, traceback
import splunk.Intersplunk as si

def parseSpan(span):
    #maxspan = [<integer> s|m|h|d]
    match = re.search("(\d*)([shdwmqy])", span)
    if match == None:
        si.generateErrorResults(" 'timeunit' argument required, such as s (seconds), h (hours), d (days), w (weeks), y (years). Optionally prefix with a number: 600s (10 minutes), 2w (2 weeks).")
        exit(-1)
    scalar, units = match.groups()
    if len(scalar) == 0:
        scalar = 1
    secs = scalar = int(scalar)
    if units == "s":
        pass
    elif units == "h":
        secs *= 60 * 60
    elif units == "d":
        secs *= 24 * 60 * 60
    elif units == "w":
        secs *= 7 * 24 * 60 * 60
    elif units == "m":
        secs *= 30 * 24 * 60 * 60
    elif units == "q":
        secs *= 365/4. * 24 * 60 * 60
    elif units == "y":
        secs *= 365 * 24 * 60 * 60
    else:
        return None, None, None
    return secs, scalar, units

UNIT_NAMES = { 's':['second', 'seconds'], 'h':['hour', 'hours'], 'd':['day','days'], 'w':['week', 'weeks'], 
               'm':['month','months'], 'q':['quarter','quarters'], 'y':['year','years'] }
def unitName(unit):
    return UNIT_NAMES.get(unit, unit)

UNIT_STRFTIME = { 'h': ['%I%p','%s'], 'd':['%b%d','%s'], 'w':['%b%d', 'week_of_%s'], 
                  'm':['%b','%s'], 'q':['%b','quarter_of_%s'], 'y':['%b','year_of_%s'] }

def seriesTime(unit, newtime):
    timefmt = UNIT_STRFTIME[unit][0]
    return time.strftime(timefmt, time.localtime(newtime))

def seriesTimeStr(unit, newtime):
    timefmt, strfmt = UNIT_STRFTIME[unit]
    return strfmt % time.strftime(timefmt, time.localtime(newtime)).lower()


def seriesName(mode, scalar, spansago, unit, newtime):

    if mode == "short":
        return "s%s" % spansago
    if mode == "exact":
        return seriesTimeStr(unit, newtime)
    else: # mode == 'relative'
        unitname = unitName(unit)
        if spansago == 0: # latest_2w
            scalartext = str(scalar) if scalar > 1 else ""
            unitname = unitname[0] if scalar * spansago < 2 else unitname[1]
            return "latest_%s%s" % (scalartext, unitname)
        else: # 4w_ago
            unitname = unitname[0] if scalar * spansago < 2 else unitname[1]
            return '%d%s_before' % (scalar * spansago, unitname) # '4q ago'

def run(spantext, seriesmode, results):
    
    try:

        secsPerSpan, scalar, unit = parseSpan(spantext)
        maxtime = -1
        # for each results
        time_data = {}
        fields_seen = {}
        span = None
        latest = None
        for result in results:
            if maxtime < 0:
                try:
                    maxtime = int(float(result['info_max_time']))
                except:
                    maxtime = int(time.time())
                maxtime -= 1 # not inclusive
            if '_time' not in result:
                raise Exception("Missing required _time field on data")
            if span == None and '_span' in result:
                span = result['_span']
            mytime = int(float(result['_time']))  
            spansago =  int((maxtime-mytime) / secsPerSpan)
            new_time = mytime + (spansago * secsPerSpan)

            if new_time not in time_data:
                time_data[new_time] = { '_time': new_time, '_span': span }
            this_row = time_data[new_time]

            spanstart = maxtime - ((spansago+1)*secsPerSpan) + 1
            series = seriesName(series_mode, scalar, spansago, unit, spanstart)
            if spansago == 0: latest = series
            acount = len(result)
            for k,v in result.items():
                if k not in ['_time', 'info_sid', 'info_max_time', 'info_min_time', 'info_search_time', 'info_sid', '_span']:
                    if k == 'count':
                        attr = series
                    else:
                        attr = '%s_%s' % (k, series)
                    this_row[attr] = result[k]
                    fields_seen[attr] = spansago

        field_order = fields_seen.items()
        field_order.sort(lambda x,y: cmp(x[1], y[1]))
        field_order = [f for f,v in field_order]
        field_order.insert(0,'_time')
        field_order.append('_span')

        results = time_data.values()
        results.sort(lambda x,y: cmp(x['_time'], y['_time']))

        si.outputResults(results, {}, fields=field_order)
    except Exception, e2:
        stack2 =  traceback.format_exc()
        si.generateErrorResults("Error '%s'. %s" % (e2, stack2))


def usage():
    si.generateErrorResults(" 'timeunit' argument required, such as s (seconds), h (hours), d (days), w (weeks), or y (years). Optionally prefix with a number: 600s (10 minutes), 2w (2 weeks). Optionally add another argument to specify the time-range label: series=[short,exact,relative]")
    exit(-1)

if __name__ == '__main__':
    try:
        series_mode = 'relative'
        (isgetinfo, sys.argv) = si.isGetInfo(sys.argv)
        argc = len(sys.argv)
        if argc != 2 and argc != 3: usage()
        if argc == 3:
            arg = sys.argv[2]
            match = re.search("(?i)series=(short|exact|relative)", sys.argv[2])
            if match == None: usage()
            series_mode = match.group(1)

        if isgetinfo:
            #  outputInfo(streaming, generating, retevs, reqsop, preop, timeorder=False, clear_req_fields=False, req_fields = None)
            si.outputInfo(False,      False,     False,   True, "addinfo", timeorder=False)

        results, dummyresults, settings = si.getOrganizedResults()

        run(sys.argv[1], series_mode, results)
    except Exception, e:
        raise
